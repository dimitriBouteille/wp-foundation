# Logging

The logs are written with the [Laravel logger](https://laravel.com/docs/10.x/logging) (Monolog).

```php
use Dbout\WpFoundation\Facades\Log;

Log::info('Order synchronized.', ['order' => $orderId]);
Log::error($exception->getMessage(), ['exception' => $exception]);
```

The levels are the ones of [PSR-3](https://www.php-fig.org/psr/psr-3/): `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert` and `emergency`.

## Channels

By default, the logs are written in `var/logs/app-YYYY-MM-DD.log`, a file per day kept 25 days.

The channels are declared in `config/logging.php`, they are added to the default `app` channel:

```php
use Dbout\WpFoundation\Facades\Dir;
use Dbout\WpFoundation\Filesystem\Directory;

return [
    'channels' => [
        'payment' => [
            'driver' => 'daily',
            'path' => Dir::getPath(Directory::Log, 'payment.log'),
            'level' => 'info',
            'days' => 90,
        ],
    ],
];
```

```php
Log::channel('payment')->info('Payment received.', ['order' => $orderId]);
```

See the [Laravel documentation](https://laravel.com/docs/10.x/logging#available-channel-drivers) for the available drivers (`single`, `daily`, `stack`, `slack`, `syslog`, ...). The default channel is changed with the `default` option.

## Logs of the package

The package logs:

- the exceptions thrown by the controllers (`error`),
- the assets that cannot be resolved (`warning`),
- the images that cannot be resized (`warning`).
