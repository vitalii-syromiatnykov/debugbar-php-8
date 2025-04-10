<?php
/*
 * This file is part of the DebugBar package.
 *
 * (c) 2013 Maxime Bouroumeau-Fuseau
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DebugBar\Bridge\SwiftMailer;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Swift_Mailer;
use Swift_Plugins_MessageLogger;

/**
 * Collects data about sent mails
 *
 * http://swiftmailer.org/
 */
class SwiftMailCollector extends DataCollector implements Renderable, AssetProvider
{
    protected \Swift_Plugins_MessageLogger $messagesLogger;

    #[\ReturnTypeWillChange] public function __construct(Swift_Mailer $mailer)
    {
        $this->messagesLogger = new Swift_Plugins_MessageLogger();
        $mailer->registerPlugin($this->messagesLogger);
    }

    #[\ReturnTypeWillChange] public function collect(): array
    {
        $mails = [];
        foreach ($this->messagesLogger->getMessages() as $msg) {
            $mails[] = [
                'to' => $this->formatTo($msg->getTo()),
                'subject' => $msg->getSubject(),
                'headers' => $msg->getHeaders()->toString()
            ];
        }

        return [
            'count' => count($mails),
            'mails' => $mails
        ];
    }

    protected function formatTo($to): string
    {
        if (!$to) {
            return '';
        }

        $f = [];
        foreach ($to as $k => $v) {
            $f[] = (empty($v) ? '' : $v . ' ') . sprintf('<%s>', $k);
        }

        return implode(', ', $f);
    }

    #[\ReturnTypeWillChange] public function getName(): string
    {
        return 'swiftmailer_mails';
    }

    #[\ReturnTypeWillChange] public function getWidgets(): array
    {
        return [
            'emails' => [
                'icon' => 'inbox',
                'widget' => 'PhpDebugBar.Widgets.MailsWidget',
                'map' => 'swiftmailer_mails.mails',
                'default' => '[]',
                'title' => 'Mails'
            ],
            'emails:badge' => [
                'map' => 'swiftmailer_mails.count',
                'default' => 'null'
            ]
        ];
    }

    #[\ReturnTypeWillChange] public function getAssets(): array
    {
        return [
            'css' => 'widgets/mails/widget.css',
            'js' => 'widgets/mails/widget.js'
        ];
    }
}
