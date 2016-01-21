<?php

namespace Pee;

abstract class AbstractRoute
{
  public abstract function accept($source);
  public abstract function getTarget();
  public abstract function __toString();
}
