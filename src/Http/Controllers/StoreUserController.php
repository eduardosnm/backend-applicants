<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Osana\Challenge\Services\Local\UsersSheet;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StoreUserController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $usersSheet = UsersSheet::getUsers();
        $id = "CSV".($usersSheet->getTotalRows());
        $user = new User(
            new Id($id),
            new Login($data["login"]),
            Type::Local(),
            new Profile(
                new Name($data["profile"]["name"]),
                new Company($data["profile"]["company"]),
                new Location($data["profile"]["location"]))
        );

        $this->localUsersRepository->add($user);
        
        return $response;
    }
}
