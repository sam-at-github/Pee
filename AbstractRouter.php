<?php
abstract class AbstractRouter implements IteratorAggregate
{
  //public abstract function __construct($policy);
  public abstract function addRoute(\AbstractRoute $route);
  public abstract function getRoute($i);
  public abstract function deleteRoute($i);
  public abstract function getIterator();
  public abstract function getLastRoute();
  public abstract function run($request); /* Now this can never be type hinted. Argh whoop. */
  public abstract function __toString();
}
