<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use GraphQL\Client;
use GraphQL\SchemaObject\RootProjectsArgumentsObject;
use GraphQL\SchemaObject\RootQueryObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * @return Collection<int,User>
     */
    public function search(): Collection
    {
        $validated = \request()->validate(['q' => ['required', 'min:3']]);
        $query = $validated['q'];
        $users = User::where('name', 'like', "%$query%")->orWhere('email', 'like', "%$query%");

        return $users->take(5)->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int,object>
     */
    public function repositories() : \Illuminate\Support\Collection
    {
        $validated = \request()->validate(['q' => ['required', 'min:3']]);
        $query = $validated['q'];

        Log::debug("Searching for repositories with query: $query");

        $rootObject = new RootQueryObject();
        $rootObject->selectProjects((new RootProjectsArgumentsObject())
            ->setMembership(true)
            ->setFirst(10)
            ->setSearch($query)
            ->setSearchNamespaces(true))
            ->selectNodes()
            ->selectId()
            ->selectName()
            ->selectCreatedAt()
            ->selectNamespace()->selectName()->selectFullName();
        $token = env('GITLAB_ACCESS_TOKEN');
        $client = new Client(env('GITLAB_URL') . '/api/graphql', ["Authorization" => 'Bearer ' . $token]);
        // @phpstan-ignore-next-line
        $results = new \Illuminate\Support\Collection($client->runQuery($rootObject)->getResults()->data->projects->nodes);
        Log::debug("Found " . $results->count() . " repositories");

        return $results;
    }
}
