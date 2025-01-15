<?php /** @noinspection PhpUnused */

namespace EHAERER\Salesforce\Authentication;

interface AuthenticationInterface
{

    /**
     * @return mixed
     */
    public function getAccessToken();

    /**
     * @return mixed
     */
    public function getInstanceUrl();
}
