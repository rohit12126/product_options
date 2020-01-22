<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Alfheim\Sanitizer\Sanitizer;

class BaseModel extends Model
{
    /**
     * make consistent w/ excopy_core/order_core
     */
    const CREATED_AT = 'date_created';

    /**
     * make consistent w/ excopy_core/order_core
     */
    const UPDATED_AT = 'date_updated';

    protected $validationRules;
    protected $errors;

    /**
     * @var array
     */
    protected $sanitize = [];

    /**
     * Validate the input before attempting to save it to the model
     *
     * @param $data
     * @param array $messages
     * @return bool
     */
    public function validate($data, $messages = [], $rules = [])
    {
        $rules = (empty($rules) ? $this->validationRules : $rules);
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails())
        {
            $this->errors = $validator->errors();
            return false;
        }

        return true;
    }

    /**
     * Get validation rules for a given model, rarely used anymore.
     * Ideally, each model would have it's own set of validation rules, based on db and business rules.
     *
     * @return mixed
     */
    public function getValidationRules()
    {
        return $this->validationRules;
    }

    /**
     * Return validation errors
     *
     * @return mixed
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Reuturn an array of the schema columns that exist for this model
     * 
     * @return mixed
     */
    public function getSchemaAttributes()
    {
        return Schema::getColumnListing($this->table);
    }

    /**
     * Override the set attribute method
     * @param string $key
     * @param mixed $value
     * @return $this|mixed
     */
    public function setAttribute($key, $value)
    {
        if (is_scalar($value)) {
            $value = $this->emptyStringToNull(trim($value));
        }
        if (array_key_exists($key, $this->sanitize) && !empty($value)) {
            $sanitizer = Sanitizer::make($this->sanitize);
            $sanitized = $sanitizer->sanitize([$key => $value]);
            $value = $sanitized[$key];
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Set empty strings to null value
     *
     * @param $string
     * @return null|string
     */
    private function emptyStringToNull($string)
    {
        //trim every value
        $string = trim($string);

        if ($string === ''){
            return null;
        }

        return $string;
    }

    /**
     * Get the validation rules for a model
     *
     * @return array
     */
    public function getRules()
    {
        return $this->_rules;
    }


    public function refresh()
    {
        $new = $this->fresh();
        $this->attributes = $new->attributes;
    }

}