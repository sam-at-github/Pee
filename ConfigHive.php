<?php

interface ConfigHive
{
  public function overlay($resource, $recursive = false);
  public function history();
  public function sync($file, $type = null);
}
