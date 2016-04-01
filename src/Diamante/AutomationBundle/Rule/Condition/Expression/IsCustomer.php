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

namespace Diamante\AutomationBundle\Rule\Condition\Expression;

use Diamante\AutomationBundle\Rule\Condition\AbstractCondition;
use Diamante\AutomationBundle\Rule\Fact\Fact;
use Diamante\UserBundle\Model\User;

class IsCustomer extends AbstractCondition
{
    public function isSatisfiedBy(Fact $fact)
    {
        $actualValue = $this->extractPropertyValue($fact);
        $user = User::fromString($actualValue);

        return $user->isDiamanteUser();
    }
}