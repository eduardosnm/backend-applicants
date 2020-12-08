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
use Tightenco\Collect\Support\Collection;

class UserHandler
{
    private $usersSheet;
    private $profilesSheet;

    public function __construct()
    {
        $this->usersSheet = UsersSheet::getUsers();
        $this->profilesSheet = ProfilesSheet::getProfiles();
    }

    public function getUser(Login $login, $limit, $begin)
    {
        $result = null;
        $total = $this->usersSheet->getTotalRows() - 1;
        $totalPages = ceil($total / $limit);
        $perPage = $limit;
        for ($i=0; $i < $totalPages;$i++){
            $users = $this->usersSheet->getRange($begin, $perPage);
            $profiles = $this->profilesSheet->getRange($begin, $perPage);
            $result = $this->findUser($users, $profiles, $login);
            if ($result instanceof User){
                break;
            }
            $begin = $perPage + 1;
            $perPage += $limit;
        }

        return $result;
    }

    public function getUsers(Login $login, $limit, $begin) : Collection
    {
        if (empty($login->getValue())){
            $users = $this->usersSheet->getRange($begin, $limit);
            $profiles = $this->profilesSheet->getRange($begin, $limit);
            return collect(array_filter($this->iterate($users, $profiles, $login)));
        }

        return collect($this->findUsers($login, $limit, $begin))->take($limit - 1);

    }

    private function findUser($users, $profiles,  $login)
    {
        $user = null;
        for ($i=0;$i < count($users); $i++){
            if ($users[$i][1] == $login->getValue()){
                $user = new User(
                    new Id($users[$i][0]),
                    new Login($users[$i][1]),
                    Type::Local(),
                    new Profile(new Name($profiles[$i][3]), new Company($profiles[$i][1]), new Location($profiles[$i][2]))
                );
                break;
            }
        }

        return $user;
    }

    private function findUsers(Login $login, $limit, $begin) : Collection
    {
        $result = [];
        $total = $this->usersSheet->getTotalRows() - 1;
        $totalPages = ceil($total / $limit);
        $perPage = $limit;
        for ($i=0; $i < $totalPages;$i++){
            $users = $this->usersSheet->getRange($begin, $perPage);
            $profiles = $this->profilesSheet->getRange($begin, $perPage);
            $result[] = array_filter($this->iterate($users, $profiles, $login));

            $begin = $perPage + 1;
            $perPage += $limit;

        }

        return collect(array_reduce($result, 'array_merge', array()))->take($limit - 1);
    }


    private function iterate($users, $profiles, $login)
    {
        return array_map(function ($user, $profile) use ($login){
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
}
