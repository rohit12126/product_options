<?php

namespace App\Core\Providers;

use Swift_SmtpTransport;
use Swift_Mailer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\Mailer;

class ExcopyMailServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('excopy.mailer', function ($app, $parameters) {
            $smtp_host = array_get($parameters, 'host');
            $smtp_port = array_get($parameters, 'port');
            $smtp_username = array_get($parameters, 'username');
            $smtp_password = array_get($parameters, 'password');
            $smtp_encryption = array_get($parameters, 'encryption');

            $from_email = array_get($parameters, 'from.email');
            $from_name = array_get($parameters, 'from.name');

            $transport = new Swift_SmtpTransport($smtp_host, $smtp_port);
            $transport->setUsername($smtp_username);
            $transport->setPassword($smtp_password);
            $transport->setEncryption($smtp_encryption);

            $swift_mailer = new Swift_Mailer($transport);

            $mailer = new Mailer($app->get('view'), $swift_mailer, $app->get('events'));
            $mailer->alwaysFrom($from_email, $from_name);
            $mailer->alwaysReplyTo($from_email, $from_name);

            return $mailer;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'excopy.mailer'
        ];
    }
}
