<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Module\Exception;

use Dbout\WpFoundation\Module\ModuleInterface;

final class ModuleException extends \LogicException
{
    public static function invalidModule(string $module): self
    {
        return new self(sprintf('The module %s must implement %s.', $module, ModuleInterface::class));
    }

    /**
     * @param list<string> $dependencies
     */
    public static function missingDependencies(string $module, array $dependencies): self
    {
        return new self(sprintf(
            'The module %s requires the modules %s, enable them in bootstrap/modules.php.',
            $module,
            implode(', ', $dependencies),
        ));
    }
}
