<?php
namespace Ytnuk;

use Composer;
use Nette;

final class Config
{

	public static function dump(Composer\Script\Event $event)
	{
		$composer = $event->getComposer();
		Composer\Autoload\includeFile(implode(DIRECTORY_SEPARATOR, [
			$vendorDir = $composer->getConfig()->get('vendor-dir'),
			'autoload.php',
		]));
		$repository = $composer->getRepositoryManager()->getLocalRepository();
		$config = [
			'extensions' => [],
		];
		foreach ($repository->getPackages() as $package) {
			$extra = array_filter($package->getExtra(), 'is_array');
			if (isset($extra['extensions'])) {
				$config['extensions'] += $extensions = array_filter(array_map([
					Nette\Neon\Neon::class,
					'decode',
				], array_filter($extra['extensions'], 'is_string')), function ($class) {
					return is_subclass_of($class instanceof Nette\Neon\Entity ? $class->value : $class, Nette\DI\CompilerExtension::class);
				});
				$config = array_merge_recursive($config, array_intersect_key($extra, $extensions));
			}
		}
		file_put_contents(implode(DIRECTORY_SEPARATOR, [
			$vendorDir,
			'config.neon',
		]), Nette\Neon\Neon::encode($config, Nette\Neon\Neon::BLOCK));
	}
}
