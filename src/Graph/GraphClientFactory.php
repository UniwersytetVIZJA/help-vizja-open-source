<?php

namespace App\Graph;

use Microsoft\Graph\GraphServiceClient;

final class GraphClientFactory
{
    public function __construct() {}

    /**
     * @param TokenAuthProvider $tokenAuthProvider
     * @return GraphServiceClient
     */
    public function create(TokenAuthProvider $tokenAuthProvider): GraphServiceClient
    {
        return new GraphServiceClient($tokenAuthProvider);
    }
}
