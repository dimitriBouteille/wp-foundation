# Routing

The router adds pages to WordPress with controllers, on top of [Symfony Routing](https://symfony.com/doc/current/routing.html). It runs on `init`: when the URL matches a route, the controller is executed, otherwise WordPress handles the request as usual.

## Controllers

A controller is a class with the `#[Route]` attribute, on the class (the `__invoke()` method is called) or on its public methods:

```php
namespace App\Controller;

use Dbout\WpFoundation\Http\Response\ResponseInterface;
use Dbout\WpFoundation\Http\Response\ViewResponse;
use Dbout\WpFoundation\Routing\Attribute\Route;

#[Route(path: '/account/register', name: 'account_register', methods: ['GET'])]
final class Register
{
    public function __invoke(): ResponseInterface
    {
        return new ViewResponse('@account/register.twig');
    }
}
```

```php
final class Training
{
    #[Route(path: '/training/{sku}/quote', name: 'training_quote', methods: ['GET'], requirements: ['sku' => '[a-z0-9-]+'])]
    public function quote(string $sku): ResponseInterface
    {
        // ...
    }

    #[Route(path: '/training/{sku}/registration', name: 'training_registration', methods: ['GET'])]
    public function registration(string $sku): ResponseInterface
    {
        // ...
    }
}
```

| Argument | Description |
|---|---|
| `path` | Path of the route, with the parameters between braces (`/training/{sku}`) |
| `name` | Name of the route, required to generate its URL |
| `methods` | Allowed HTTP methods, all methods when empty |
| `requirements` | Regular expressions of the parameters, see [Symfony](https://symfony.com/doc/current/routing.html#parameters-validation) |

## Discovery

The controllers are found from the PSR-4 namespaces of `config/router.php`. The directories of a namespace are resolved from the Composer autoloader and the class names are derived from the file paths, so the files must follow PSR-4 (one class per file, named like the file).

```php
// config/router.php
return [
    'namespaces' => [
        'App\\Controller\\',
    ],
];
```

A namespace can contain `*` wildcards, i.e. to find the controllers of all the modules:

```php
'namespaces' => [
    'App\\Modules\\*\\Controller\\',
],
```

A namespace without wildcard must exist in the Composer autoloader, otherwise an exception is thrown. A namespace with wildcards matching no directory is ignored.

## Actions

The controller is built by the container, so the dependencies of its constructor are injected. The parameters of the action are resolved by name:

- the route parameters, cast to the type of the parameter (`int`, `float`, `bool` or `string`),
- the current `Symfony\Component\HttpFoundation\Request`,
- the other classes, resolved by the container.

```php
use Symfony\Component\HttpFoundation\Request;

#[Route(path: '/orders/{id}', name: 'order', methods: ['GET'])]
public function __invoke(int $id, Request $request, OrderRepository $orders): ResponseInterface
{
    $order = $orders->find($id) ?? throw new NotFoundHttpException();
    $page = $request->query->getInt('page', 1);

    // ...
}
```

The action must return a `ResponseInterface`.

## Responses

The responses are in the `Dbout\WpFoundation\Http\Response` namespace:

| Response | Description |
|---|---|
| `ViewResponse($template, $context = [], $status = 200)` | Renders a Twig template, see [Views](views.md) |
| `RedirectResponse($url, $status = 302)` | Redirects with `wp_safe_redirect()`, an external URL is replaced by the home URL (see the `allowed_redirect_hosts` filter) |
| `JsonResponse($data, $status = 200)` | Sends JSON with `wp_send_json()` |
| `Response($content, $status = 200)` | Sends a raw content |
| `NotFoundResponse()` | Renders the WordPress 404 template |
| `ErrorResponse($status, $message = '')` | Renders an error page with `wp_die()` |

The controllers are executed on `init`, before the WordPress main query. The template of a `ViewResponse` is therefore rendered at the end of `template_redirect`, once the main query is done and the redirections (canonical, WooCommerce, ...) are processed. As the URL of a controller matches no WordPress content, the 404 flag of the main query and the HTTP status are overridden.

## HTTP exceptions

An HTTP exception thrown by a controller is converted into a response:

```php
use Dbout\WpFoundation\Http\Exception\AccessDeniedHttpException;
use Dbout\WpFoundation\Http\Exception\HttpException;
use Dbout\WpFoundation\Http\Exception\NotFoundHttpException;

throw new NotFoundHttpException();          // WordPress 404 template
throw new AccessDeniedHttpException();      // error page with the 403 status
throw new HttpException(410, 'Gone.');      // error page with the given status
```

The other exceptions are logged then thrown again.

## Generate URLs

```php
use Dbout\WpFoundation\Facades\Router;

Router::generate('training_quote', ['sku' => 'first-aid']);    // /training/first-aid/quote
Router::generate('training_quote', ['sku' => 'first-aid', 'step' => 2]);    // /training/first-aid/quote?step=2
```

The parameters that are not in the path are added to the query string. Use `UrlGeneratorInterface::ABSOLUTE_URL` as third argument for an absolute URL.

### Custom paths

URLs that are not routes (WordPress pages, WooCommerce pages, ...) can be generated with the same function, with the `customPaths` of `config/router.php`:

```php
// config/router.php
return [
    'customPaths' => [
        'cart' => fn () => wc_get_page_permalink('cart'),
        'contact' => fn () => get_permalink(get_page_by_path('contact')),
    ],
];
```

```php
Router::generate('cart');    // the URL of the WooCommerce cart page
```

The `home` custom path (home URL) is defined by default.

### In the templates

Add the `RouterExtension` to the Twig extensions:

```php
// config/twig.php
return [
    'extensions' => [
        Dbout\WpFoundation\Routing\View\Twig\Extensions\RouterExtension::class,
    ],
];
```

```twig
<a href="{{ page_url('training_quote', { sku: product.sku }) }}">Request a quote</a>
```
