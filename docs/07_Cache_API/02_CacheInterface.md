The `CacheInterface` interface defines functions that need to be implemented. To create a new cache that implements `CacheInterface` you must implement following functions:

* [loadData](#the-loaddata-function)
* [saveData](#the-savedata-function)
* [getTime](#the-gettime-function)
* [purgeCache](#the-purgecache-function)

Find a [template](#template) at the end of this file.

# Functions

## The `loadData` function

This function loads data from the cache and returns the data in the same format provided to the [saveData](#the-savedata-function) function.

```PHP
loadData(): mixed
```

## The `saveData` function

This function stores the given data into the cache and returns the object instance.

```PHP
saveData(mixed $data): self
```

## The `getTime` function

## The `purgeCache` function

This function removes any data from the cache that is not within the given duration. The duration is specified in seconds and defines the period between now and the oldest item to keep.

```PHP
purgeCache(int $duration): null
```

# Template

This is the bare minimum template for a new cache:

```PHP
<?php
class MyTypeCache implements CacheInterface {
    public function loadData(){
        // Implement your algorithm here!
        return null;
    }

    public function saveData($data){
        // Implement your algorithm here!

        return $this;
    }

    public function getTime(){
        // Implement your algorithm here!

        return false;
    }

    public function purgeCache($duration){
        // Implement your algorithm here!
    }
}
// Imaginary empty line!
```
