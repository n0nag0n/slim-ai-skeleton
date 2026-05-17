<?php

namespace App\Debug\Tracy;

use Slim\App;
use Tracy\Debugger;

class ExtensionLoader
{
    public function __construct(App $app)
    {
        if (!Debugger::isEnabled()) {
            return;
        }

        $bar = Debugger::getBar();

        $bar->addPanel(new RequestPanel);
        $bar->addPanel(new ResponsePanel);
        $bar->addPanel(new RoutesPanel($app));
        $bar->addPanel(new SessionPanel($app->getContainer()->get(\App\Util\SessionInterface::class)));
        $bar->addPanel(new DatabasePanel);
    }
}
