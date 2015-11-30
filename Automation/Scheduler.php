<?php
/*
 * Copyright (c) 2015 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Diamante\AutomationBundle\Automation;


use Diamante\AutomationBundle\Rule\Action\ActionInterface;
use Diamante\AutomationBundle\Rule\Fact\Fact;
use Symfony\Bridge\Monolog\Logger;

class Scheduler
{
    protected $logger;

    /**
     * @var ActionInterface[]
     */
    protected $queue;

    protected $hasErrors = false;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param Fact $fact
     */
    public function run(Fact $fact)
    {
        foreach ($this->queue as $action) {
            try {
                $action->getContext()->setFact($fact);
                $action->execute();

                if ($action->getContext()->hasErrors()) {
                    $this->hasErrors = true;
                    foreach ($action->getContext()->getErrors() as $error) {
                        $this->logger->error($error);
                    }
                }

            } catch (\Exception $e) {
                $this->hasErrors = true;
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function reset()
    {
        $this->queue = [];
        $this->hasErrors = false;
    }

    public function addAction(ActionInterface $action)
    {
        $this->queue[] = $action;
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return $this->hasErrors;
    }
}