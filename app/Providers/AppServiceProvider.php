<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;

class AppServiceProvider extends ServiceProvider
{


    public function boot(): void
    {
        //
        Mail::extend('brevo', function (array $config) {
            return (new BrevoTransportFactory())->create(new Dsn(
                'brevo+api',
                'default',
                config('services.brevo.key')
            ));
        });
    }
}
