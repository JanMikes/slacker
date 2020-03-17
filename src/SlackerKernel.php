<?php declare(strict_types=1);

namespace JanMikes\Slacker;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class SlackerKernel extends BaseKernel
{
    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        $contents = require __DIR__ . '/../config/bundles.php';

        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }


	/**
	 * @inheritDoc
	 */
	public function registerContainerConfiguration(LoaderInterface $loader): void
	{
        $confDir = __DIR__ . '/../config';

        $loader->load($confDir.'/{packages}/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/' . $this->environment . '/*' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}' . self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_' . $this->environment . self::CONFIG_EXTS, 'glob');
	}
}
