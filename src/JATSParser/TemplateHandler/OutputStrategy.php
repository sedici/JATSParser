<?php

namespace JATSParser\TemplateHandler;

abstract class OutputStrategy {

  public abstract static function generateOutput($plugin, $fileMgr, $journalId, $localeKey, $fileId, $htmlString, $configuration, $metadata);

}