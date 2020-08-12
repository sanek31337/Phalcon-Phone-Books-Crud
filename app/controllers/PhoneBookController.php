<?php
declare(strict_types=1);

use App\Exceptions\PhoneBookItemException;
use App\Models\PhoneBookItem;
use League\Fractal\Manager;
use League\Fractal\Serializer\JsonApiSerializer;
use Phalcon\Messages\Messages;

/**
 * Class PhoneBookController
 * @property Phalcon\Http\Request $request
 * @property Phalcon\Http\Response $response
 * @property Phalcon\Mvc\View $view
 */
class PhoneBookController extends ControllerBase
{
    const DEFAULT_LIMIT = 50;

    public function listAction()
    {
        $limit = $this->request->getQuery('limit', null, self::DEFAULT_LIMIT);
        $offset = $this->request->getQuery('offset', null, 0);
        $searchPhrase = $this->request->getQuery('searchPhrase', null);

        $builder = new \Phalcon\Mvc\Model\Query\Builder();

        $builder->addFrom(PhoneBookItem::class);

        if ($searchPhrase)
        {
            $builder
                ->where('first_name LIKE :searchPhrase:')
                ->orWhere('last_name LIKE :searchPhrase:')
                ->setBindParams([
                    'searchPhrase' => '%' . $searchPhrase . '%'
                ])
                ->setBindTypes([
                    'searchPhrase' => PDO::PARAM_STR
                ]);
        }

        $params = [
            'builder' => $builder,
            'limit' => $limit,
            'page' => $offset
        ];

        $paginator = new Phalcon\Paginator\Adapter\QueryBuilder($params);

        $phoneBookItems = $paginator->paginate();

        $manager = new Manager();
        $manager->setSerializer(new JsonApiSerializer());

        $entitiesList = [];
        foreach ($phoneBookItems->getItems() as $item)
        {
            $phoneBookItemEntity = new \App\Entities\PhoneBookItem();

            $phoneBookItemEntity->setId((int)$item->getId());
            $phoneBookItemEntity->setFirstName($item->getFirstName());
            $phoneBookItemEntity->setLastName($item->getLastName());
            $phoneBookItemEntity->setPhoneNumber($item->getPhoneNumber());
            $phoneBookItemEntity->setTimeZone($item->getTimeZone());
            $phoneBookItemEntity->setCountryCode($item->getCountryCode());
            $phoneBookItemEntity->setInsertedOn(new DateTime($item->getInsertedOn()));
            $phoneBookItemEntity->setUpdatedOn(new DateTime($item->getUpdatedOn()));

            $entitiesList[] = $phoneBookItemEntity;
        }

        $manager->getSerializer()->meta([
            'total' => $phoneBookItems->getTotalItems(),
            'usedParameters' => [
                'limit' => $limit,
                'offset' => $offset
            ]
        ]);

        $resource = new \League\Fractal\Resource\Collection($entitiesList, new \App\Services\PhoneBookItemsTransformer(), 'item');

        $resource->setPaginator(new App\Services\PhalconFrameworkPaginatorAdapterV4($phoneBookItems));

        return $manager->createData($resource)->toJson();
    }

    public function viewAction(int $id)
    {
        $phoneBookItem = PhoneBookItem::findFirst($id);

        if ($phoneBookItem === null)
        {
            $this->logger->error('There is no item found with id: "' . $id . "'");
            throw new PhoneBookItemException('There is no item found with id: "' . $id . "'", 200);
        }

        $phoneBookItemEntity = new \App\Entities\PhoneBookItem();

        $phoneBookItemEntity->setId((int)$phoneBookItem->getId());
        $phoneBookItemEntity->setFirstName($phoneBookItem->getFirstName());
        $phoneBookItemEntity->setLastName($phoneBookItem->getLastName());
        $phoneBookItemEntity->setPhoneNumber($phoneBookItem->getPhoneNumber());
        $phoneBookItemEntity->setTimeZone($phoneBookItem->getTimeZone());
        $phoneBookItemEntity->setCountryCode($phoneBookItem->getCountryCode());
        $phoneBookItemEntity->setInsertedOn(new DateTime($phoneBookItem->getInsertedOn()));
        $phoneBookItemEntity->setUpdatedOn(new DateTime($phoneBookItem->getUpdatedOn()));

        $manager = new Manager();
        $manager->setSerializer(new JsonApiSerializer());

        $resource = new \League\Fractal\Resource\Item($phoneBookItemEntity, new \App\Services\PhoneBookItemsTransformer(), 'phoneBookItem');

        return $manager->createData($resource)->toJson();
    }

    /**
     * @param Messages $messages
     * @throws PhoneBookItemException
     */
    private function sendValidationResponseMessage(Messages $messages)
    {
        if ($messages->count() > 0)
        {
            $preparedMesage = 'Reasons: ';
            foreach ($messages as $key => $message)
            {
                if ($key != 0)
                {
                    $preparedMesage .= '; ';
                }

                $preparedMesage .= $message;
            }

            $this->logger->error($preparedMesage);
            throw new PhoneBookItemException($preparedMesage, 200);
        }
    }

    public function addItemAction()
    {
        try
        {
            $rawBody = $this->checkRawBodyFormat();

            $phoneBookItem = $this->findPhoneBookItemByFirstNameAndPhoneNumber($rawBody->firstName, $rawBody->phoneNumber);

            if ($phoneBookItem)
            {
                $this->logger->error("The phone book item is already exists");
                throw new PhoneBookItemException("The phone book item is already exists", 200);
            }
            else
            {
                $phoneBookItem = new PhoneBookItem();

                $phoneBookItem->setFirstName($rawBody->firstName);
                $phoneBookItem->setLastName($rawBody->lastName);
                $phoneBookItem->setPhoneNumber($rawBody->phoneNumber);
                $phoneBookItem->setCountryCode($rawBody->countryCode);
                $phoneBookItem->setTimeZone($rawBody->timeZone);

                $result = $phoneBookItem->save();

                if ($result === false)
                {
                    $messages = $phoneBookItem->getMessages();

                    try
                    {
                        $this->sendValidationResponseMessage($messages);
                    }
                    catch (PhoneBookItemException $exception)
                    {
                        throw $exception;
                    }
                }
                else
                {
                    $payload = [
                        'status' => 'success',
                        'message' => 'The item has been successfully created.'
                    ];

                    return new Phalcon\Http\Response(json_encode($payload), 200);
                }
            }
        }
        catch (PhoneBookItemException $exception)
        {
            throw $exception;
        }
    }

    private function checkRawBodyFormat()
    {
        $rawBody = $this->request->getJsonRawBody(false);

        if (!$rawBody)
        {
            $this->logger->error("Invalid POST request format. It should contains valid JSON structure.");
            throw new PhoneBookItemException("Invalid format. Post request should be valid json.");
        }

        $phoneBookValidation = new \App\Services\PhoneBookItemValidation();
        $phoneBookValidation->setHttpRequestService($this->container->get('http_request'));
        $phoneBookValidation->setCacheService($this->container->get('cache'));

        /** @var Messages $messages */
        $messages = $phoneBookValidation->validate($rawBody);

        try
        {
            $this->sendValidationResponseMessage($messages);
        }
        catch (PhoneBookItemException $exception)
        {
            throw $exception;
        }

        return $rawBody;
    }

    /**
     * @param string $firstName
     * @param string $phoneNumber
     * @return \App\Models\PhoneBookItem|\Phalcon\Mvc\Model\ResultInterface
     */
    private function findPhoneBookItemByFirstNameAndPhoneNumber($firstName, $phoneNumber)
    {
        $phoneBookItem = PhoneBookItem::findFirst(
            [
                'columns' => '*',
                'conditions' => 'first_name = :firstName: AND phone_number = :phoneNumber:',
                'bind' => [
                    'firstName' => $firstName,
                    'phoneNumber' => $phoneNumber
                ]
            ]
        );

        return $phoneBookItem;
    }

    public function updateItemAction(int $id)
    {
        try
        {
            $rawBody = $this->checkRawBodyFormat();

            $phoneBookItem = PhoneBookItem::findFirst($id);
            if ($phoneBookItem === null)
            {
                throw new PhoneBookItemException('There is no item found with id: "' . $id . "'", 200);
            }

            $existedPhoneBookItem = $this->findPhoneBookItemByFirstNameAndPhoneNumber($rawBody->firstName, $rawBody->phoneNumber);

            if ($existedPhoneBookItem)
            {
                $this->logger->error("The phone book item is already exists");
                throw new PhoneBookItemException("The phone book item with the first name and phone number is already exists.", 200);
            }

            $phoneBookItem->setFirstName($rawBody->firstName);
            $phoneBookItem->setLastName($rawBody->lastName);
            $phoneBookItem->setPhoneNumber($rawBody->phoneNumber);
            $phoneBookItem->setCountryCode($rawBody->countryCode);
            $phoneBookItem->setTimeZone($rawBody->timeZone);

            /** @var \Phalcon\Mvc\Model $phoneBookItem */
            $result = $phoneBookItem->update();

            if ($result === false)
            {
                $messages = $phoneBookItem->getMessages();

                foreach ($messages as $message) {
                    echo $message . PHP_EOL;
                }
            }

            $payload = [
                'status' => 'success',
                'message' => 'The item has been successfully updated.'
            ];

            return new Phalcon\Http\Response(json_encode($payload), 200);
        }
        catch (PhoneBookItemException $exception)
        {
            throw $exception;
        }
    }

    public function deleteItemAction(int $id)
    {
        /** @var \Phalcon\Mvc\Model $phoneBookItem */
        $phoneBookItem = PhoneBookItem::findFirst($id);

        if ($phoneBookItem === null)
        {
            throw new PhoneBookItemException('There is no item found with id: ' . $id, 200);
        }

        if (!$phoneBookItem->delete())
        {
            $this->logger->error("Invalid POST request format. It should contains valid JSON structure.");
            throw new Exception("Invalid format. Post request should be valid json.");
        }

        $payload = [
            'status' => 'success',
            'message' => 'The item has been successfully deleted.'
        ];

        return new Phalcon\Http\Response(json_encode($payload), 200);
    }

    public function route404Action()
    {
        $payload = [
            'message' => 'The requested route is unsupported.'
        ];

        return new Phalcon\Http\Response(json_encode($payload), 404);
    }
}