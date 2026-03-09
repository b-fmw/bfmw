<?php
/*
 * Author: Cédric BOUHOURS
 * This code is provided under the terms of the Creative Commons Attribution-NonCommercial-NoDerivatives 4.0 International License.
 * Attribution — You must give appropriate credit, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests the licensor endorses you or your use.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material, you may not distribute the modified material.
 * No additional restrictions — You may not apply legal terms or technological measures that legally restrict others from doing anything the license permits.
 */

namespace bfmw;

use bfmw\core\Authenticator;
use bfmw\core\Interceptor;
use bfmw\core\Csrf;
use bfmw\core\DBConnector;
use bfmw\core\EnvLoader;
use bfmw\core\Framework;
use bfmw\core\Helpers;
use bfmw\core\PageGenerator;
use bfmw\core\ParametersEncoder;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

/**
 * The `Application` class serves as the foundational structure for initializing, managing system helpers,
 * routing, and binding interceptors within the application. It handles configuration loading, authentication,
 * and routing logic for both administrative and general use cases.
 *
 * This abstract class must be extended by concrete implementations and provides mechanisms such as
 * autoload registration, environment configuration setup, and application request handling.
 */
abstract class Application
{
    /**
     * Folder containing page scripts.
     */
    private const string GENERATORS_FOLDER = "generators/";
    /**
     * Subfolder for administration generators.
     */
    private const string ADMIN_FOLDER = "Admin/";
    /**
     * Default page name.
     */
    private const string DEFAULT_GENERATOR = "Accueil";

    /**
     * Global helpers instance.
     * @var Helpers
     */
    public static Helpers $globalHelpers;
    /**
     * Global database connection instance.
     * @var DBConnector
     */
    public static DBConnector $dataHelpers;
    /**
     * Framework instance.
     * @var Framework
     */
    private Framework $framework;
    /**
     * Authentication service.
     * @var Authenticator
     */
    protected Authenticator $authenticator;
    /**
     * Session key to store the current page.
     * @var string
     */
    public string $sessionPage;

    /**
     * Initializes the static components of the application.
     *
     * Configures the autoloader and instantiates global helpers.
     * Must be called before instantiating the class.
     *
     * @return void
     */
    public static function init(): void
    {
        spl_autoload_register([self::class,'autoload']);
        self::$globalHelpers = new Helpers();
    }

    /**
     * Automatic class loader.
     *
     * @param string $class Fully qualified name of the class to load.
     * @return void
     */
    private static function autoload(string $class): void
    {
        $path = "../".str_replace('\\', '/', $class) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }

    /**
     * Application constructor.
     *
     * Initializes the environment, checks authentication, and routes the request.
     *
     * @param string $env_file Path to the environment configuration file.
     * @param Authenticator $authenticator Authentication service instance.
     * @param string $sessionPage Session key used to store the active page.
     * @param string $timezone Timezone to use (default: "Europe/Paris").
     * @param Interceptor $interceptors Pre-routing interception handler.
     * @param Framework $framework Base framework instance.
     *
     * @throws RuntimeException If init() has not been called beforehand.
     */
    public function __construct(string $env_file, Authenticator $authenticator, string $sessionPage, DBConnector $dbConnector, Interceptor $interceptors = new Interceptor(), string $timezone = "Europe/Paris", Framework $framework = new Framework())
    {
        if (self::$globalHelpers === null) {
            throw new RuntimeException("Use init before making an instance of Application");
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        new EnvLoader()->load($env_file);
        $this->framework = $framework;

        $this->executeFirstProcess();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!new Csrf()->validateFromPost()) {
                unset($_POST);
            }
        }

        if (!isset($_POST['bfmw_orig_encoded_parameters'])) {
            new ParametersEncoder()->cleanAllParameters();
        }

        date_default_timezone_set($timezone);

        self::$dataHelpers = $dbConnector;
        self::$dataHelpers->connect();

        $this->authenticator = $authenticator;
        $authentication = $authenticator->authenticate();
        if ($authentication === false) {
            unset($_SESSION);
            throw new RuntimeException("You do not have permission to view the page you requested. Please try again.");
        }
        $authenticator->saveAsSession($authentication);

        $this->sessionPage = $sessionPage;

        if (!$interceptors->bindingInterceptor()) {
            if (!$interceptors->frontInterceptor()) {
                $this->route();
            } else {
                self::$globalHelpers->redirectWithoutQueryParam("p");
                exit;
            }
        }
    }

    /**
     * Returns the authenticator instance used by the test application.
     *
     * @return Authenticator Authentication service configured for this app.
     */
    public function getAuthenticators(): Authenticator
    {
        return $this->authenticator;
    }

    /**
     * Executes initial response processes.
     *
     * Sends HTTP headers and sanitizes global variables.
     *
     * @return void
     */
    private function executeFirstProcess(): void
    {
        $this->framework->sendHeaders();
        $this->framework->sanitize();
    }

    /**
     * Handles request routing.
     *
     * Determines the page to display based on GET parameters,
     * user rights (admin or not), and file existence.
     *
     * @return void
     */
    private function route(): void
    {
        if (!isset($_GET['bfmw_orig_p'])) {
            $_GET['bfmw_orig_p'] = self::DEFAULT_GENERATOR;
        }
        $_SESSION[$this->sessionPage] = $_GET['bfmw_orig_p'];
        if ($this->authenticator->isAdmin()) {
            if (!file_exists(self::GENERATORS_FOLDER . self::ADMIN_FOLDER . $_GET['bfmw_orig_p'] . ".php")) {
                if (!file_exists(self::GENERATORS_FOLDER . $_GET['bfmw_orig_p'] . ".php")) {
                    $_SESSION[$this->sessionPage] = self::DEFAULT_GENERATOR;
                } else {
                    $_SESSION[$this->sessionPage] = $_GET['bfmw_orig_p'];
                }
            } else {
                $_SESSION[$this->sessionPage] = self::ADMIN_FOLDER . $_GET['bfmw_orig_p'];
            }
        } else {
            if (!file_exists(self::GENERATORS_FOLDER . $_GET['bfmw_orig_p'] . ".php")) {
                $_SESSION[$this->sessionPage] = self::DEFAULT_GENERATOR;
            } else {
                $_SESSION[$this->sessionPage] = $_GET['bfmw_orig_p'];
            }
        }

        $this->run();
    }

    /**
     * Factory method to instantiate a PageGenerator class dynamically.
     *
     * Uses Reflection to verify that the class exists, is a subclass of PageGenerator,
     * and is instantiable before creating it.
     *
     * @param string $generator Fully Qualified Class Name (FQCN) of the generator.
     * @param array $args Arguments to pass to the generator's constructor.
     * @return PageGenerator The instantiated generator.
     * @throws RuntimeException If the class is invalid, missing, or not a PageGenerator.
     */
    public function makeGenerator(string $generator, array $args = []): PageGenerator
    {
        try {
            $reflector = new ReflectionClass($generator);
            if (!$reflector->isSubclassOf(PageGenerator::class)) {
                throw new RuntimeException("La classe '$generator' n'est pas un PageGenerator valide.");
            }
            if (!$reflector->isInstantiable()) {
                throw new RuntimeException("La classe '$generator' n'est pas instanciable (peut-être est-elle abstraite ?).");
            }

            $instance = $reflector->newInstanceArgs($args);

            if (!$instance instanceof PageGenerator) {
                throw new RuntimeException("L'instance créée n'est pas de type PageGenerator.");
            }

            return $instance;

        } catch (ReflectionException $e) {
            throw new RuntimeException("Impossible de trouver la classe '$generator'.", 0, $e);
        }
    }

    /**
     * Abstract method to start the application execution.
     *
     * This method should be implemented by the concrete application class
     * to trigger the logic associated with the resolved route (e.g., calling makeGenerator).
     *
     * @return void
     */
    abstract public function run(): void;

    /**
     * Returns the favicon URL used by the application.
     *
     * @return string Relative or absolute favicon path.
     */
    abstract public function getFavIcon(): string;
}
