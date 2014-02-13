<?php namespace Waavi\Translation\Commands;

//https://gist.github.com/dylian94/6522673/raw/c71b2adc9142995b1540c6367f31636fd89d55c4/TranslatorImportCommand.php

use Illuminate\Cache\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Waavi\Translation\Providers\LanguageProvider as LanguageProvider;
use Waavi\Translation\Providers\LanguageEntryProvider as LanguageEntryProvider;

class TranslatorWipeCommand extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'translator:wipe';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Wipe all translations from database';

	/**
	 *  Create a new mixed loader instance.
	 *
	 * @param  \Waavi\Lang\Providers\LanguageProvider $languageProvider
	 * @param  \Waavi\Lang\Providers\LanguageEntryProvider $languageEntryProvider
	 * @param  \Illuminate\Foundation\Application $app
	 */
	public function __construct($languageProvider, $languageEntryProvider)
	{

		// Waavi language entry provider, used to get/set the translation entries from/in the database
		$this->languageEntryProvider = $languageEntryProvider;

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
		$wipe_all = $this->option('force');

		// If a package is specified only load language files for this package

		$this->info('deleting...');

		$q=$this->languageEntryProvider->createModel()->newQuery();

		if(empty($wipe_all))
			$q->where('locked','<>','1');

		$q->delete();


		$this->info('### Finished wiping translations! ###');
		$this->output->writeLn('');

		$this->info('Clearing cache...');
		$this->output->writeLn('');

		\Cache::flush();
		$this->info('DONE');
		$this->output->writeLn('');
	}



	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(

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
			array(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'wipe all entries (default is to keep existing ones)'
				),
			);
	}

}
