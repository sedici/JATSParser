<?php

namespace JATSParser\TemplateHandler;

interface OutputStrategy {

  public static function generateOutput($plugin, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata, $ojsConfiguration);

}