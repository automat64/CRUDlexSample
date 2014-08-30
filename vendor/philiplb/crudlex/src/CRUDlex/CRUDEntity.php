<?php

/*
 * This file is part of the CRUDlex package.
 *
 * (c) Philip Lehmann-Böhm <philip@philiplb.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CRUDlex;

use CRUDlex\CRUDEntityDefinition;

use CRUDlex\CRUDData;

class CRUDEntity {

    protected $definition;

    protected $entity = array();

    public function __construct(CRUDEntityDefinition $definition) {
        $this->definition = $definition;
    }

    public function set($field, $value) {
        $this->entity[$field] = $value;
    }

    public function get($field) {
        return $this->entity[$field];
    }

    public function validate(CRUDData $data) {

        $fields = $this->definition->getEditableFieldNames();
        $errors = array();
        $valid = true;
        foreach ($fields as $field) {
            $errors[$field] = array('required' => false, 'unique' => false, 'input' => false);

            // Check for required
            if ($this->definition->isRequired($field) && (!key_exists($field, $this->entity) || !$this->entity[$field])) {
                $errors[$field]['required'] = true;
                $valid = false;
            }

            // Check for uniqueness
            if ($this->definition->isUnique($field) && key_exists($field, $this->entity) && $this->entity[$field]) {
                $params = array($field => $this->entity[$field]);
                $paramsOperators = array($field => '=');
                if ($this->entity['id'] !== null) {
                    $params['id'] = $this->entity['id'];
                    $paramsOperators['id'] = '!=';
                }
                $amount = intval($data->countBy($this->definition->getTable(), $params, $paramsOperators, true));
                if ($amount > 0) {
                    $errors[$field]['unique'] = true;
                    $valid = false;
                }
            }

            // Check for int type
            $type = $this->definition->getType($field);
            if ($type == 'int' && $this->entity[$field] !== '' && (string)(int)$this->entity[$field] != $this->entity[$field]) {
                $errors[$field]['input'] = true;
                $valid = false;
            }

            // Check for date type
            if ($type == 'date' && $this->entity[$field] !== '' && \DateTime::createFromFormat('Y-m-d', $this->entity[$field]) === false) {
                $errors[$field]['input'] = true;
                $valid = false;
            }

            // Check for reference type
            if ($type == 'reference' && $this->entity[$field] !== '') {
                $params = array('id' => $this->entity[$field]);
                $paramsOperators = array('id' => '=');
                $amount = $data->countBy($this->definition->getReferenceTable($field), $params, $paramsOperators, false);
                if ($amount == 0) {
                    $errors[$field]['input'] = true;
                    $valid = false;
                }
            }
        }
        return array('valid' => $valid, 'errors' => $errors);
    }

}