<?php

namespace hoksi;

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Codeigniter4 Validation Mapper
 *
 * @author Hoksi (hoksi2k@hanmail.net)
 */
class Validation {

    /**
     * Codeigniter3
     * 
     * @var Codeigniter
     */
    protected $ci;

    /**
     * Codeigniter3 form_validation
     * 
     * @var form_validation
     */
    protected $validater;

    /**
     * Stores the actual rules that should
     * be ran against $data.
     *
     * @var array
     */
    protected $rules;

    /**
     * The data that should be validated,
     * where 'key' is the alias, with value.
     *
     * @var array
     */
    protected $data;

    public function __construct() {
        $this->ci = & get_instance();

        $this->ci->load->library('form_validation');
        $this->validater = & $this->ci->form_validation;

        $this->reset();
    }

    /**
     * Sets an individual rule and custom error messages for a single field.
     *
     * The custom error message should be just the messages that apply to
     * this field, like so:
     *
     *    [
     *        'rule' => 'message',
     *        'rule' => 'message'
     *    ]
     *
     * @param string $field
     * @param string $rule
     * @param array  $errors
     *
     * @return $this
     */
    public function setRule($field, $rule, $errors = array()) {
        $rule_exists = false;

        for ($i = 0; $i < count($this->rules); $i++) {
            if ($this->rules[$i]['field'] == $field) {
                $this->rules[$i] = array(
                    'field' => $field,
                    'label' => '',
                    'rules' => $rule,
                    'errors' => $errors,
                );

                $rule_exists = true;
                break;
            }
        }

        if ($rule_exists == false) {
            $this->rules[] = array(
                'field' => $field,
                'label' => '',
                'rules' => $rule,
                'errors' => $errors,
            );
        }

        return $this;
    }

    /**
     * Stores the rules that should be used to validate the items.
     * Rules should be an array formatted like:
     *
     *    [
     *        'field' => 'rule1|rule2'
     *    ]
     *
     * The $errors array should be formatted like:
     *    [
     *        'field' => [
     *            'rule' => 'message',
     *            'rule' => 'message
     *        ],
     *    ]
     *
     * @param array $rules
     * @param array $errors // An array of custom error messages
     *
     * @return \CodeIgniter\Validation\Validation
     */
    public function setRules($rules, $errors = array()) {
        if (is_array($rules) && !empty($rules)) {
            $this->rules = array();
            foreach ($rules as $field => $rule) {
                $this->setRule($field, $rule, (isset($errors[$field]) ? $errors[$field] : array()));
            }
        }

        return $this;
    }

    /**
     * Takes a Request object and grabs the data to use from its
     * POST array values.
     *
     * @param \CodeIgniter\HTTP\RequestInterface $request
     *
     * @return \CodeIgniter\Validation\Validation
     */
    public function withRequest($request = null) {
        $this->data = $this->ci->input->post();

        return $this;
    }

    /**
     * Check; runs the validation process, returning true or false
     * determining whether or not validation was successful.
     *
     * @param mixed    $value  Value to validation.
     * @param string   $rule   Rule.
     * @param string[] $errors Errors.
     *
     * @return bool True if valid, else false.
     */
    public function check($value, $rule, $errors = []) {
        $this->reset();
        $this->setRule('check', $rule, $errors);

        return $this->run(array('check' => $value));
    }

    /**
     * Runs the validation process, returning true/false determining whether
     * or not validation was successful.
     *
     * @param array  $data  The array of data to validate.
     * @param string $group The pre-defined group of rules to apply.
     *
     * @return bool
     */
    public function run($data = null, $group = null) {
        if (!empty($data)) {
            $this->validater->set_data($data);
        } else {
            $this->validater->set_data($this->data);
        }

        $this->validater->set_rules($this->rules);

        return $this->validater->run($group);
    }

    /**
     * Returns all of the rules currently defined.
     *
     * @return array
     */
    public function getRules() {
        return $this->rules;
    }

    /**
     * Checks to see if the rule for key $field has been set or not.
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasRule($field) {
        return $this->validater->has_rule($field);
    }

    /**
     * Resets the class to a blank slate. Should be called whenever
     * you need to process more than one array.
     *
     * @return mixed
     */
    public function reset() {
        $this->rules = array();
        $this->data = array();

        $this->validater->reset_validation();

        return $this;
    }

    /**
     * Returns the error(s) for a specified $field (or empty string if not
     * set).
     *
     * @param string $field Field.
     *
     * @return string Error(s).
     */
    public function getError($field = null) {
        return $this->validater->error($field);
    }

    /**
     * Returns the array of errors that were encountered during
     * a run() call. The array should be in the followig format:
     *
     *    [
     *        'field1' => 'error message',
     *        'field2' => 'error message',
     *    ]
     *
     * @return array
     */
    public function getErrors() {
        return $this->validater->error_array();
    }

    /**
     * Sets the error for a specific field. Used by custom validation methods.
     *
     * @param string $field
     * @param string $error
     *
     * @return \CodeIgniter\Validation\Validation
     */
    public function setError($field, $error) {
        $this->validater->set_message($field, $error);

        return $this;
    }

}
