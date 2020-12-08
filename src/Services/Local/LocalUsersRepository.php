<?php

namespace Osana\Challenge\Services\Local;

use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\UserNotFoundException;
use Osana\Challenge\Domain\Users\UsersRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tightenco\Collect\Support\Collection;

class LocalUsersRepository implements UsersRepository
{
    const DEFAULT_LIMIT = 21;

    public function findByLogin(Login $login, int $limit = 0): Collection
    {
        $limit = $limit ? ($limit + 1) : self::DEFAULT_LIMIT;
        $begin = 2;

        $usersHandler = new UserHandler();

        return $usersHandler->getUsers($login, $limit, $begin);

    }

    public function getByLogin(Login $login, int $limit = 0): User
    {
        $limit = $limit ? ($limit + 1) : self::DEFAULT_LIMIT;
        $begin = 2;

        $usersHandler = new UserHandler();

        return $usersHandler->getUser($login, $limit, $begin);

    }

    public function add(User $user): void
    {
        $usersSheet = UsersSheet::getUsers();
        $profilesSheet = ProfilesSheet::getProfiles();

        $insertInRow = $usersSheet->getTotalRows() + 1;
        $usersSheet->setCell("A{$insertInRow}", $user->getId()->getValue());
        $usersSheet->setCell("B{$insertInRow}", $user->getLogin()->getValue());
        $usersSheet->setCell("C{$insertInRow}", $user->getType()->getValue());

        $profilesSheet->setCell("A{$insertInRow}", $user->getId()->getValue());
        $profilesSheet->setCell("B{$insertInRow}", $user->getProfile()->getCompany()->getValue());
        $profilesSheet->setCell("C{$insertInRow}", $user->getProfile()->getLocation()->getValue());
        $profilesSheet->setCell("D{$insertInRow}", $user->getProfile()->getName()->getValue());

    }


    private function getUser(array $users, array $profiles, Login $login)
    {
        for ($i=0;$i < count($users); $i++){
            if ($users[$i][1] == $login->getValue()){
                return new User(
                    new Id($users[$i][0]),
                    new Login($users[$i][1]),
                    Type::Local(),
                    new Profile(new Name($profiles[$i][3]), new Company($profiles[$i][1]), new Location($profiles[$i][2]))
                );
            }
        }

        return new UserNotFoundException();

    }
}
