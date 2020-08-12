<?php

namespace App\Models;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Behavior\Timestampable;

/**
 * PhoneBook
 * 
 * @autogenerated by Phalcon Developer Tools
 * @date 2020-08-10, 14:46:39
 */
class PhoneBookItem extends Model
{
    /**
     *
     * @var integer
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $first_name;

    /**
     *
     * @var string
     */
    protected $last_name;

    /**
     *
     * @var string
     */
    protected $phone_number;

    /**
     *
     * @var string
     */
    protected $country_code;

    /**
     *
     * @var string
     */
    protected $time_zone;

    /**
     *
     * @var string
     */
    protected $insertedOn;

    /**
     *
     * @var string
     */
    protected $updatedOn;

    /**
     * Method to set the value of field id
     *
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Method to set the value of field first_name
     *
     * @param string $first_name
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Method to set the value of field last_name
     *
     * @param string $last_name
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Method to set the value of field phone_number
     *
     * @param string $phone_number
     * @return $this
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * Method to set the value of field country_code
     *
     * @param string $country_code
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;

        return $this;
    }

    /**
     * Method to set the value of field time_zone
     *
     * @param string $time_zone
     * @return $this
     */
    public function setTimeZone($time_zone)
    {
        $this->time_zone = $time_zone;

        return $this;
    }

    /**
     * Method to set the value of field insertedOn
     *
     * @param string $insertedOn
     * @return $this
     */
    public function setInsertedOn($insertedOn)
    {
        $this->insertedOn = $insertedOn;

        return $this;
    }

    /**
     * Method to set the value of field updatedOn
     *
     * @param string $updatedOn
     * @return $this
     */
    public function setUpdatedOn($updatedOn)
    {
        $this->updatedOn = $updatedOn;

        return $this;
    }

    /**
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Returns the value of field last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Returns the value of field phone_number
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * Returns the value of field country_code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * Returns the value of field time_zone
     *
     * @return string
     */
    public function getTimeZone()
    {
        return $this->time_zone;
    }

    /**
     * Returns the value of field insertedOn
     *
     * @return string
     */
    public function getInsertedOn()
    {
        return $this->insertedOn;
    }

    /**
     * Returns the value of field updatedOn
     *
     * @return string
     */
    public function getUpdatedOn()
    {
        return $this->updatedOn;
    }

    /**
     * Initialize method for model.
     */
    public function initialize()
    {
        $this->setSchema("phone_book");
        $this->setSource("phone_book_items");

        $this->addBehavior(
            new Timestampable(
                [
                    'beforeValidationOnCreate' => [
                        'field'  => ['insertedOn', 'updatedOn'],
                        'format' => 'Y-m-d H:i:s',
                    ],
                    'beforeValidationOnUpdate' => [
                        'field' => 'updatedOn',
                        'format' => 'Y-m-d H:i:s'
                    ]
                ],
            )
        );
    }

    /**
     * Allows to query a set of records that match the specified conditions
     *
     * @param mixed $parameters
     * @return PhoneBook[]|PhoneBook|\Phalcon\Mvc\Model\ResultSetInterface
     */
    public static function find($parameters = null): \Phalcon\Mvc\Model\ResultsetInterface
    {
        return parent::find($parameters);
    }

    /**
     * Allows to query the first record that match the specified conditions
     *
     * @param mixed $parameters
     * @return PhoneBook|\Phalcon\Mvc\Model\ResultInterface
     */
    public static function findFirst($parameters = null)
    {
        return parent::findFirst($parameters);
    }

}