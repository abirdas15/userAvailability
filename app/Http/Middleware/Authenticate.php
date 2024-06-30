<?php

namespace App\Http\Middleware;


use Closure;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class Authenticate extends Middleware
{
    // Protected properties for the ResourceServer and TokenRepository
    protected $server;
    protected $repository;

    /**
     * Constructor to initialize the ResourceServer and TokenRepository.
     *
     * @param ResourceServer $server
     * @param TokenRepository $repository
     */
    public function __construct(ResourceServer $server, TokenRepository $repository)
    {
        $this->server = $server;
        $this->repository = $repository;
    }

    /**
     * Handle an incoming request.
     *
     * @param $request The incoming HTTP request.
     * @param Closure $next The next middleware or request handler.
     * @param mixed ...$guards Additional guards (if any).
     * @return mixed The response from the next middleware or the final response.
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Convert the Laravel request to a PSR-7 request
        $psr = (new PsrHttpFactory(
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory,
            new Psr17Factory
        ))->createRequest($request);

        try {
            // Validate the authenticated request using the ResourceServer
            $psr = $this->server->validateAuthenticatedRequest($psr);

            // Find the token in the repository using the token ID from the validated request
            $token = $this->repository->find(
                $psr->getAttribute('oauth_access_token_id')
            );

            // Check if the token is expired
            $currentDate = new \DateTime();
            $tokenExpireDate = new \DateTime($token->expires_at);
            $isAuthenticated = $tokenExpireDate > $currentDate;

            if ($isAuthenticated) {
                // If authenticated, find the user associated with the token
                $oauthUser = $this->repository->findForUser($token->id, $token->user_id);
                $user = User::find($oauthUser->user_id);

                // Add the authenticated user to the request
                $request->request->add(['sessionUser' => $user]);
                $request->query->add(['sessionUser' => $user]);

                // Proceed with the next middleware/request handler
                return $next($request);
            }

            // If the token is expired, return a 401 response
            return response()->json(['message' => 'The token has been expired!'], 401);

        } catch (OAuthServerException $e) {
            // Log the exception message
            Log::error('OAuthServerException: ' . $e->getMessage());

            // If an OAuth server exception occurs, return a 403 response
            return response()->json(['message' => 'Invalid Token!'], 403);
        }
    }

}
