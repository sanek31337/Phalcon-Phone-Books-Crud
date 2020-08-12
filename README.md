Set up:

1) Make a copy of `.env.sample` to `.env` in the `app/env/` directory and replace the values.

2) You can generate the `ENCRYPTION_KEY` environment variable by running 
`php -r "echo base64_encode(random_bytes(40)) . PHP_EOL;"` on the command line

    * cd into the `keys` directory and generate your public and private keys like so: `openssl genrsa -out private.key 2048` 
    then  `openssl rsa -in private.key -pubout -out public.key`. These are needed for encrypting and decrypting tokens
    
    * You will need to change the permissions of the private and public keys you create in the previous step to the following:
    ``` chgrp www-data -R keys ``` Then ``` chmod 600 keys/private.key ```

3) Import dump sql file from app/db folder.

4) Run composer install command in the root project directory.

Auth section setup (Based on padlock library *https://github.com/tegaphilip/padlock*):

Supported only Implicit Grant OAuth2 flow:

Send a `GET` request to `/api/v1/oauth/authorize` URI with the following parameters:
    - client_id: user
    - response_type: token
NOTE: This grant returns an access token immediately. It does not return a refresh token. 

**CRUD resource endpoints:**

**Note it's required OAuth2 token to be allowed make request to CRUD operations. OAuth2 access token can be requested by using 
Implicit Grant OAuth2 flow method which described above.**

**Read all items**

To get list of all existed phone book items need to send a `GET` request to `/phoneBook/items/` URI endpoint.

To limit number of returned items should be provided `limit` parameter in the URL. By default using 50 items per page.

To use pagination need to provide `offset` paramet in the URL.

To search items which contains specific term in the first name and last name fields uses `searchPhrase` parameter.

**Read specific item**

To get specific item by id need to send a `GET` request to `/phoneBook/items/{id}` URI endpoint with item's id. E.g. `phoneBook/items/2`, 
where 2 - is id of the item.

**Create item**

To create new item need to send a `POST` request to `/phoneBook/items/` with mandatory JSON structure in the POST body.

**Note phone number only availabe in the following format +12 223 444224666

`{
    "firstName": "<First Name>",
    "lastName": "<Last Name",
    "phoneNumber": "<Phone Number>",
    "countryCode": "<Country Code>",
    "timeZone": "<Time Zone>"
}`

**Update item**
To update existed item need to send a `PUT` request to `/phoneBook/items/{id}` with provided `id` of the item and with mandatory JSON structure in the POST body.

**Note phone number only availabe in the following format +12 223 444224666

`{
    "firstName": "<First Name>",
    "lastName": "<Last Name",
    "phoneNumber": "<Phone Number>",
    "countryCode": "<Country Code>",
    "timeZone": "<Time Zone>"
}`

**Delete item**
To delete item need to send a `DELETE` request to `/phoneBook/items/{id}` with provided `id`.