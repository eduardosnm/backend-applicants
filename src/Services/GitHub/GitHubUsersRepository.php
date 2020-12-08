<?php

namespace Osana\Challenge\Services\GitHub;

use GuzzleHttp\Client;
use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\UsersRepository;
use Tightenco\Collect\Support\Collection;

class GitHubUsersRepository implements UsersRepository
{
    const DEFAULT_LIMIT = 20;

    public function findByLogin(Login $name, int $limit = 0): Collection
    {
        $limit = $limit ? ($limit + 1) : self::DEFAULT_LIMIT;

        $client = new Client(['base_uri' => env('API_GITHUB_BASE_URI')]);
        $response = $client->request('GET', "users?per_page={$limit}");

        if ($response->getStatusCode() != 200) {
            throw new \Exception("User not found", 400);
        }

        $users = json_decode($response->getBody()->getContents());
        $result = collect();
        foreach ($users as $user){
            if (preg_match("#^{$name->getValue()}(.*)$#i", $user->login)) {
                $response = $client->request('GET', "users/{$user->login}");
                $info = json_decode($response->getBody()->getContents());
                $result->push(
                    new User(
                        new Id($user->id),
                        new Login($user->login),
                        Type::GitHub(),
                        new Profile(
                            new Name($info->name ?? ''),
                            new Company($info->company ?? ''),
                            new Location($info->location ?? ''))
                    )
                );
            }
        }

        return $result;
    }

    public function getByLogin(Login $name, int $limit = 0): User
    {
        $client = new Client(['base_uri' => env('API_GITHUB_BASE_URI')]);
        $response = $client->request('GET', "users/{$name->getValue()}");
        if ($response->getStatusCode() != 200) {
            throw new \Exception("User not found", 400);
        }

        $response = json_decode($response->getBody()->getContents());

        return new User(
            new Id($response->id),
            new Login($response->login),
            Type::GitHub(),
            new Profile(
                new Name($response->name ?? ''),
                new Company($response->company ?? ''),
                new Location($response->location ?? ''))
        );
    }

    public function add(User $user): void
    {
        throw new OperationNotAllowedException();
    }
}
