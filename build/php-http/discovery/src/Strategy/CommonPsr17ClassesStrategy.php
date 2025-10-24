<?php

namespace BackdropOpenAI\Http\Discovery\Strategy;

use BackdropOpenAI\Psr\Http\Message\RequestFactoryInterface;
use BackdropOpenAI\Psr\Http\Message\ResponseFactoryInterface;
use BackdropOpenAI\Psr\Http\Message\ServerRequestFactoryInterface;
use BackdropOpenAI\Psr\Http\Message\StreamFactoryInterface;
use BackdropOpenAI\Psr\Http\Message\UploadedFileFactoryInterface;
use BackdropOpenAI\Psr\Http\Message\UriFactoryInterface;
/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * Don't miss updating src/Composer/Plugin.php when adding a new supported class.
 */
final class CommonPsr17ClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [RequestFactoryInterface::class => ['BackdropOpenAI\Phalcon\Http\Message\RequestFactory', 'BackdropOpenAI\Nyholm\Psr7\Factory\Psr17Factory', 'BackdropOpenAI\GuzzleHttp\Psr7\HttpFactory', 'BackdropOpenAI\Http\Factory\Diactoros\RequestFactory', 'BackdropOpenAI\Http\Factory\Guzzle\RequestFactory', 'BackdropOpenAI\Http\Factory\Slim\RequestFactory', 'BackdropOpenAI\Laminas\Diactoros\RequestFactory', 'BackdropOpenAI\Slim\Psr7\Factory\RequestFactory', 'BackdropOpenAI\HttpSoft\Message\RequestFactory'], ResponseFactoryInterface::class => ['BackdropOpenAI\Phalcon\Http\Message\ResponseFactory', 'BackdropOpenAI\Nyholm\Psr7\Factory\Psr17Factory', 'BackdropOpenAI\GuzzleHttp\Psr7\HttpFactory', 'BackdropOpenAI\Http\Factory\Diactoros\ResponseFactory', 'BackdropOpenAI\Http\Factory\Guzzle\ResponseFactory', 'BackdropOpenAI\Http\Factory\Slim\ResponseFactory', 'BackdropOpenAI\Laminas\Diactoros\ResponseFactory', 'BackdropOpenAI\Slim\Psr7\Factory\ResponseFactory', 'BackdropOpenAI\HttpSoft\Message\ResponseFactory'], ServerRequestFactoryInterface::class => ['BackdropOpenAI\Phalcon\Http\Message\ServerRequestFactory', 'BackdropOpenAI\Nyholm\Psr7\Factory\Psr17Factory', 'BackdropOpenAI\GuzzleHttp\Psr7\HttpFactory', 'BackdropOpenAI\Http\Factory\Diactoros\ServerRequestFactory', 'BackdropOpenAI\Http\Factory\Guzzle\ServerRequestFactory', 'BackdropOpenAI\Http\Factory\Slim\ServerRequestFactory', 'BackdropOpenAI\Laminas\Diactoros\ServerRequestFactory', 'BackdropOpenAI\Slim\Psr7\Factory\ServerRequestFactory', 'BackdropOpenAI\HttpSoft\Message\ServerRequestFactory'], StreamFactoryInterface::class => ['BackdropOpenAI\Phalcon\Http\Message\StreamFactory', 'BackdropOpenAI\Nyholm\Psr7\Factory\Psr17Factory', 'BackdropOpenAI\GuzzleHttp\Psr7\HttpFactory', 'BackdropOpenAI\Http\Factory\Diactoros\StreamFactory', 'BackdropOpenAI\Http\Factory\Guzzle\StreamFactory', 'BackdropOpenAI\Http\Factory\Slim\StreamFactory', 'BackdropOpenAI\Laminas\Diactoros\StreamFactory', 'BackdropOpenAI\Slim\Psr7\Factory\StreamFactory', 'BackdropOpenAI\HttpSoft\Message\StreamFactory'], UploadedFileFactoryInterface::class => ['BackdropOpenAI\Phalcon\Http\Message\UploadedFileFactory', 'BackdropOpenAI\Nyholm\Psr7\Factory\Psr17Factory', 'BackdropOpenAI\GuzzleHttp\Psr7\HttpFactory', 'BackdropOpenAI\Http\Factory\Diactoros\UploadedFileFactory', 'BackdropOpenAI\Http\Factory\Guzzle\UploadedFileFactory', 'BackdropOpenAI\Http\Factory\Slim\UploadedFileFactory', 'BackdropOpenAI\Laminas\Diactoros\UploadedFileFactory', 'BackdropOpenAI\Slim\Psr7\Factory\UploadedFileFactory', 'BackdropOpenAI\HttpSoft\Message\UploadedFileFactory'], UriFactoryInterface::class => ['BackdropOpenAI\Phalcon\Http\Message\UriFactory', 'BackdropOpenAI\Nyholm\Psr7\Factory\Psr17Factory', 'BackdropOpenAI\GuzzleHttp\Psr7\HttpFactory', 'BackdropOpenAI\Http\Factory\Diactoros\UriFactory', 'BackdropOpenAI\Http\Factory\Guzzle\UriFactory', 'BackdropOpenAI\Http\Factory\Slim\UriFactory', 'BackdropOpenAI\Laminas\Diactoros\UriFactory', 'BackdropOpenAI\Slim\Psr7\Factory\UriFactory', 'BackdropOpenAI\HttpSoft\Message\UriFactory']];
    public static function getCandidates($type)
    {
        $candidates = [];
        if (isset(self::$classes[$type])) {
            foreach (self::$classes[$type] as $class) {
                $candidates[] = ['class' => $class, 'condition' => [$class]];
            }
        }
        return $candidates;
    }
}
