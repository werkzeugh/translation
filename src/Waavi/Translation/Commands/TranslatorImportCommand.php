<?php namespace Waavi\Translation\Commands;

//https://gist.github.com/dylian94/6522673/raw/c71b2adc9142995b1540c6367f31636fd89d55c4/TranslatorImportCommand.php

use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Waavi\Translation\Providers\LanguageProvider as LanguageProvider;
use Waavi\Translation\Providers\LanguageEntryProvider as LanguageEntryProvider;

class TranslatorImportCommand extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'translator:import';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import file translations into the database (Azion way)';

	/**
	 *  Create a new mixed loader instance.
	 *
	 * @param  \Waavi\Lang\Providers\LanguageProvider $languageProvider
	 * @param  \Waavi\Lang\Providers\LanguageEntryProvider $languageEntryProvider
	 * @param  \Illuminate\Foundation\Application $app
	 */
	public function __construct($languageProvider, $languageEntryProvider, $fileLoader)
	{
		// Waavi language provider, used to get the languages defined in the database
		$this->languageProvider = $languageProvider;

		// Waavi language entry provider, used to get/set the translation entries from/in the database
		$this->languageEntryProvider = $languageEntryProvider;

		// Waavi file loader, used to retrieve language entries from language files
		$this->fileLoader = $fileLoader;

		// Laravel Filesystem, used to search for language files
		$this->finder = new Filesystem();

		// The base path to operate from
		$this->path = app_path() . '/lang';

		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		// Get the console argument for 'package'
		$package = $this->argument('package');

		// If a package is specified only load language files for this package
		if (!empty($package))
		{
			$this->output->writeln('');
			$this->info('### Loading translations for the package "' . $package . '" ###');


			// If the package exists in the workbench directory then set the base path to the workbench path
			if (is_dir(base_path('workbench/' . $package . '/src/lang')))
			{
				$this->path = base_path('workbench/' . $package . '/src/lang');
			}
			// else, if the package exists in the vendor directory then set the base path to the vendor path
			elseif (is_dir(base_path('vendor/' . $package . '/src/lang')))
			{
				$this->path = base_path('vendor/' . $package . '/src/lang');
			}
			// else trow an error and exit
			else
			{
				$this->output->writeLn('');
				$this->error('                                              ');
				$this->error('  The specified package could not be found!   ');
				$this->error('                                              ');
				$this->output->writeLn('');
				exit();
			}

			$this->output->writeln('');

			// Load the translations for the specified package
			$this->loadTranslations();

			$this->output->writeLn('');
			$this->info('### Finished importing translations! ###');
			$this->output->writeLn('');
		}
		// else load all language files
		else
		{
			$this->output->writeln('');
			$this->info('### Loading translations for the current app ###');
			$this->output->writeLn('');

			// Load the app language files
			$this->loadTranslations();

			$this->output->writeln('');
			$this->info('### Loading translations for all packages ###');
			$this->output->writeLn('');

			// Get all directories in the vendor directory
			$vendors = $this->finder->directories(base_path('vendor'));

			// Get all directories in the workbench directory and add them to the $vendors array
			$vendors = array_merge($vendors, $this->finder->directories(base_path('workbench')));

			foreach($vendors as $vendor)
			{
				$packages = $this->finder->directories($vendor);

				foreach ($packages as $package)
				{
					$this->path = $package . '/src/lang';

					if(is_dir($this->path))
					{
						// Load the package language files
						$this->loadTranslations();
					}
				}

			}

			$this->info('### Finished importing translations! ###');
			$this->output->writeLn('');
		}

		$this->info('Clearing cache...');
		$this->output->writeLn('');

		\Cache::flush();
		$this->info('DONE');
		$this->output->writeLn('');
	}

	private function loadTranslations()
	{

		$localeDirs = $this->finder->directories($this->path);

		$namespace = null;

		if (stripos($this->path, '/src/lang') !== false)
		{
			$packagePath = str_replace('/src/lang', '', $this->path);
            if(strstr($packagePath, '/vendor/'))
            {
                $namespace = str_replace(base_path() . '/vendor/', '', $packagePath);
            }            
            elseif(strstr($packagePath, '/workbench/'))
            {
                $namespace = str_replace(base_path() . '/workbench/', '', $packagePath);
            }
			$namespace = explode('/', $namespace)[1];
		}

		foreach ($localeDirs as $localeDir)
		{
			$locale = str_replace($this->path . '/', '', $localeDir);
			$language = $this->languageProvider->findByLocale($locale);
			if ($language)
			{
				$langFiles = $this->finder->files($localeDir);
				foreach ($langFiles as $langFile)
				{
					$this->comment('Loading translations from: ' . $langFile);

					$group = str_replace(array($localeDir . '/', '.php'), '', $langFile);

					$lines = require $langFile;

					$this->languageEntryProvider->loadArray($lines, $language, $group, $namespace, $locale == $this->fileLoader->getDefaultLocale());

					$this->info('Imported ' . count($lines, COUNT_RECURSIVE) . ' translation lines.');
					$this->output->writeLn('');
				}
			}
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array(
				'package',
				InputArgument::OPTIONAL,
				'(Vendor\Package) The package to load the translations from, default is all!'
			),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			//array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}

}
