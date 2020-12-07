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

        $usersSheet = UsersSheet::getUsers();
        $profilesSheet = ProfilesSheet::getProfiles();

        if (empty($login->getValue())){
            $users = $usersSheet->getRange($begin, $limit);
            $profiles = $profilesSheet->getRange($begin, $limit);
            return collect(array_filter($this->getUsers($users, $profiles, $login)));
        }

        $result = [];
        $total = $usersSheet->getTotalRows() - 1;
        $totalPages = ceil($total / $limit);
        $perPage = $limit;
        for ($i=0; $i < $totalPages;$i++){
            $users = $usersSheet->getRange($begin, $perPage);
            $profiles = $profilesSheet->getRange($begin, $perPage);
            $result[] = array_filter($this->getUsers($users, $profiles, $login));

            $begin = $perPage + 1;
            $perPage += $limit;

        }

        return collect(array_reduce($result, 'array_merge', array()))->take($limit - 1);
    }

    public function getByLogin(Login $login, int $limit = 0): User
    {
        $limit = $limit ? ($limit + 1) : self::DEFAULT_LIMIT;
        $begin = 2;
        $usersSheet = UsersSheet::getUsers();
        $profilesSheet = ProfilesSheet::getProfiles();

        $result = null;
        $total = $usersSheet->getTotalRows() - 1;
        $totalPages = ceil($total / $limit);
        $perPage = $limit;
        for ($i=0; $i < $totalPages;$i++){
            $users = $usersSheet->getRange($begin, $perPage);
            $profiles = $profilesSheet->getRange($begin, $perPage);
            $result = $this->getUser($users, $profiles, $login);
            if ($result instanceof User){
                return $result;
            }
            $begin = $perPage + 1;
            $perPage += $limit;

        }

    }

    public function add(User $user): void
    {
        // TODO: implement me
    }

    private function getUsers(array $users, array $profiles, Login $login)
    {
        return array_map(function ($user, $profile) use ($login) {
            if (preg_match("#^{$login->getValue()}(.*)$#i", $user[1])) {
                return new User(
                    new Id($user[0]),
                    new Login($user[1]),
                    Type::Local(),
                    new Profile(new Name($profile[3]), new Company($profile[1]), new Location($profile[2]))
                );
            }
            return null;
        }, $users, $profiles);
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
