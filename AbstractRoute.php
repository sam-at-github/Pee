<?php
abstract class AbstractRoute
{
  public abstract function accept($source);
  public abstract function target();
  public abstract function __toString();
}