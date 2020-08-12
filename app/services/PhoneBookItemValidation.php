<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Phalcon\Cache;
use Phalcon\Container;
use Phalcon\Messages\Message;
use Phalcon\Messages\Messages;
use Phalcon\Validation;

/**
 * Class PhoneBookItemValidation
 * @package App\Services
 */
class PhoneBookItemValidation extends Validation
{
    /**
     * @var Client|null
     */
    protected $httpRequest = null;

    /**
     * @var Cache|null
     */
    protected $cache = null;

    private $countriesCodesList = [];
    private $timeZonesList = [];

    const CACHE_ITEMS_TTL = 3600;

    public function initialize()
    {
        $this->add(
            'firstName',
            new Validation\Validator\PresenceOf(
                [
                    'message' => 'The first name is required.'
                ]
            )
        );

        $this->add(
            'phoneNumber',
            new Validation\Validator\PresenceOf(
                [
                    'message' => 'The phone number is required.'
                ]
            )
        );

        $this->add(
            'phoneNumber',
            new Validation\Validator\Regex(
                [
                    'message' => 'The phone number should be in the proper format. E.g. +12 223 444224455',
                    'pattern' => '/\+12 [0-9]{3} [0-9]{9}/'
                ]
            )
        );

        $this->add(
            'countryCode',
             new Validation\Validator\Callback(
                 [
                     'callback' => function($data){
                        return array_key_exists($data->countryCode, (array)$this->countriesCodesList);
                     },
                     'message' => "Incorrect country code"
                 ]
             )
        );

        $this->add(
            'timeZone',
            new Validation\Validator\Callback(
                [
                    'callback' => function($data){
                        return array_key_exists($data->timeZone, (array)$this->timeZonesList);
                    },
                    'message' => "Incorrect time zone"
                ]
            )
        );
    }


    /**
     * @param Client $client
     */
    public function setHttpRequestService($client)
    {
        $this->httpRequest = $client;
    }

    /**
     * @param Cache $cache
     */
    public function setCacheService($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Executed before validation
     * @param array $data
     * @param object $entity
     * @param Message $messages
     * @param Validation
     * @return bool
     * @throws Exception
     */
    public function beforeValidation($data, $entity, $messages)
    {
        if ($this->httpRequest === null)
        {
            throw new Exception("There is no http request service provided.");

            return false;
        }

        try
        {
            $this->checkCountriesList();
            $this->checkTimeZonesList();

            return true;
        }
        catch (GuzzleException $exception)
        {
            //TODO add logger

            $messages->setMessage(
                'Guzzle Error' . $exception->getMessage()
            );

            return false;
        }
        catch (Cache\Exception\InvalidArgumentException $e)
        {
            //TODO add logger

            return false;
        }
    }

    private function checkCountriesList()
    {
        if (count($this->countriesCodesList) == 0)
        {
            try
            {
                $this->countriesCodesList = $this->getCountriesList();
            }
            catch (GuzzleException $exception)
            {
                throw $exception;
            }
            catch (Cache\Exception\InvalidArgumentException $e)
            {
                throw $e;
            }
        }
    }

    private function checkTimeZonesList()
    {
        if (count($this->timeZonesList) == 0)
        {
            try
            {
                $this->timeZonesList = $this->getTimeZonesList();
            }
            catch (GuzzleException $exception)
            {
                throw $exception;
            }
            catch (Cache\Exception\InvalidArgumentException $e)
            {
                throw $e;
            }
        }
    }

    /**
     * @return mixed|string
     * @throws Cache\Exception\InvalidArgumentException
     * @throws GuzzleException
     */
    private function getCountriesList()
    {
        if ($this->cache !== null)
        {
            if ($this->cache->has('countriesList'))
            {
                return (array) $this->cache->get('countriesList');
            }
        }

        try
        {
            $countriesList = $this->getCountriesListFromAPI();

            if ($this->cache !== null)
            {
                $this->cache->set('countriesList', $countriesList, self::CACHE_ITEMS_TTL);
            }

            return $countriesList;
        }
        catch (GuzzleException $exception)
        {
            //TODO add logging

            throw $exception;
        }
        catch (Exception $e)
        {
            //TODO add logging
            throw $e;
        }
    }

    /**
     * @return mixed|string
     * @throws Cache\Exception\InvalidArgumentException
     * @throws GuzzleException
     */
    private function getTimeZonesList()
    {
        if ($this->cache !== null)
        {
            if ($this->cache->has('timeZonesList'))
            {
                return $this->cache->get('timeZonesList');
            }
        }

        try
        {
            $timeZonesList = $this->getTimeZonesListFromAPI();

            if ($this->cache !== null)
            {
                $this->cache->set('timeZonesList', $timeZonesList, self::CACHE_ITEMS_TTL);
            }

            return $timeZonesList;
        }
        catch (GuzzleException $exception)
        {
            //TODO add logging

            throw $exception;
        }
        catch (Exception $e)
        {
            //TODO add logging
            throw $e;
        }
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     * @return array
     */
    private function getCountriesListFromAPI()
    {
        try
        {
            $response = $this->httpRequest->request('GET', 'https://api.hostaway.com/countries');

            $content = $response->getBody()->getContents();

            $content = $this->transformResponse($content);

            if ($content)
            {
                return $content;
            }
            else
            {
                throw new Exception("There are no list of country code in the API response", 200);
            }
        }
        catch (GuzzleException $exception)
        {
            throw $exception;
        }
    }

    /**
     * @param $response
     * @return array|null
     */
    private function transformResponse($response)
    {
        $decodedResponse = json_decode($response, true);

        if ($decodedResponse)
        {
            if ($decodedResponse['status'] === 'success' && isset($decodedResponse['result']) && is_array($decodedResponse['result']))
            {
                return $decodedResponse['result'];
            }
        }

        return null;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     * @return array
     */
    private function getTimeZonesListFromAPI()
    {
        try
        {
            $response = $this->httpRequest->request('GET', 'https://api.hostaway.com/timezones');

            $content = $response->getBody()->getContents();

            $content = $this->transformResponse($content);

            if ($content)
            {
                return $content;
            }
            else
            {
                throw new Exception("There are no list of country code in the API response", 200);
            }
        }
        catch (GuzzleException $exception)
        {
            throw $exception;
        }
    }
}