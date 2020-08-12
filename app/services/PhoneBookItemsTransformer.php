<?php

namespace App\Services;

use App\Entities\PhoneBookItem;
use League\Fractal\TransformerAbstract;

class PhoneBookItemsTransformer extends TransformerAbstract
{
    public function transform(PhoneBookItem $phoneBookItem)
    {
        return [
                'id' => $phoneBookItem->getId(),
                'First Name' => $phoneBookItem->getFirstName(),
                'Last Name' => $phoneBookItem->getLastName(),
                'Phone Number' => $phoneBookItem->getPhoneNumber(),
                'County Code' => $phoneBookItem->getCountryCode(),
                'Time Zone' => $phoneBookItem->getTimeZone()
        ];
    }
}