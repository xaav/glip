<?php
/*
 * Copyright (C) 2008, 2009 Patrik Fimml, Sjoerd de Jong
 *
 * This file is part of glip.
 *
 * glip is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.

 * glip is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with glip.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
* GitPath regulates all paths in glip
*/
class GitPath implements ArrayAccess, Iterator, Countable
{
  protected
    $parts = array(),
    $partsCount = 0,
    $refTree = false;
    
  function __construct($arg)
  {
    if (!is_array($arg))
    {
      $arg = explode('/',(string)$arg);
    }
    
    if (count($arg) == 0)
    {
      // assume a reference to root
      $this->refTree = true;
    }
    else
    {
      foreach ($arg as $index => $part)
      {
        if (strlen(trim($part))>0)
        {
          $this->parts[] = trim($part);
        }
        elseif ($index == count($arg)-1)
        {
          // last element is empty
          $this->refTree = true;
        }
      }
    }    
  }

  public function isSingle()
  {
    return count($this->parts) <= 1;
  }

  public function isRoot()
  {
    return count($this->parts) == 0;
  }

  public function refTree()
  {
    return $this->refTree;
  }
  
  public function getTreePart()
  {
    if ($this->refTree())
    {
      return (string) $this;
    } 
    else
    {
      $dir = $this->parts;
      array_pop($dir);
      array_push($dir, '');
      return (string) new GitPath($dir);
    } 
  }
  
  public function getShifted()
  {
    $dir = $this->parts;
    array_shift($dir);
    return new GitPath($dir);
  }
  
  public function getPopped()
  {
    $dir = $this->parts;
    array_pop($dir);
    return new GitPath($dir);
  }
  
  public function refBlob()
  {
    return !$this->refTree();
  }
  
  public function getBlobPart()
  {
    if ($this->refBlob())
    {
      return $this[-1];
    } 
    else
    {
      return "";
    }    
  }
  
  public function __toString()
  {
    return implode('/',$this->parts).($this->refTree()?'/':'');
  }

  /**
   * Returns the number of subdirectories in this path
   *
   * @return int The number of array
   */
  public function count()
  {
    return count($this->parts);
  }

  /**
   * Reset the parts array to the beginning (implements the Iterator interface).
   */
  public function rewind()
  {
    reset($this->parts); 
    $this->partsCount = count($this->parts); 
  }

  /**
   * Get the key associated with the current path part (implements the Iterator interface).
   *
   * @return int position of the key
   */
  public function key()
  {
    return key($this->parts); 
  }

  /**
   * Returns the current part (implements the Iterator interface).
   *
   * @return mixed The part string
   */
  public function current()
  {
    return current($this->parts); 
  }

  /**
   * Moves to the next part (implements the Iterator interface).
   */
  public function next()
  {
    next($this->parts); 
    --$this->partsCount; 
  }

  /**
   * Returns true if the current part is valid (implements the Iterator interface).
   *
   * @return boolean The validity of the current element; true if it is valid
   */
  public function valid()
  {
    return ($this->partsCount > 0);
  }

  /**
   * Returns true if the part exists (implements the ArrayAccess interface).
   *
   * @param  string $index  The index of the part
   *
   * @return bool true if the error exists, false otherwise
   */
  public function offsetExists($index)
  {
    if (count($this->parts) == 0)
    {
      $index = 0;  
    }
    while ($index<0)
    {
      $index += count($this->parts);
    } 
    return isset($this->parts[$index]); 
  }

  /**
   * Returns the part associated with the index (implements the ArrayAccess interface).
   *
   * @param  string $index  The offset of the value to get, negative index counts from the last element
   *
   * @return string
   */
  public function offsetGet($index)
  {
    if (count($this->parts) == 0)
    {
      $index = 0;  
    }
    while ($index<0)
    {
      $index += count($this->parts);
    } 
    return isset($this->parts[$index]) ? $this->parts[$index] : null; 
  }

  /**
   * Sets the indexed part to value (implements the ArrayAccess interface).
   *
   * @param string $index
   * @param string $value
   *
   */
  public function offsetSet($index, $value)
  {
    if (count($this->parts) == 0)
    {
      $index = 0;  
    }
    while ($index<0)
    {
      $index += count($this->parts);
    }
    if ($index > count($this->parts))
    {
      $index = count($this->parts);
    }
    $this->parts[$index] = $value;
  }

  /**
   * Ignored because not allowed to manipulate this way
   *
   * @param int $index
   */
  public function offsetUnset($index)
  {
  }
}
