# Laravel Crud Generator

[![Total Downloads](https://poser.pugx.org/f8projects/laravelcrudgenerator/downloads)](https://packagist.org/packages/f8projects/laravelcrudgenerator)
[![Monthly Downloads](https://poser.pugx.org/f8projects/laravelcrudgenerator/d/monthly)](https://packagist.org/packages/f8projects/laravelcrudgenerator)
[![Daily Downloads](https://poser.pugx.org/f8projects/laravelcrudgenerator/d/daily)](https://packagist.org/packages/f8projects/laravelcrudgenerator)
[![Latest Stable Version](https://poser.pugx.org/f8projects/laravelcrudgenerator/v/stable)](https://packagist.org/packages/f8projects/laravelcrudgenerator)
[![Latest Unstable Version](https://poser.pugx.org/f8projects/laravelcrudgenerator/v/unstable)](https://packagist.org/packages/f8projects/laravelcrudgenerator)
[![License](https://poser.pugx.org/f8projects/laravelcrudgenerator/license)](https://packagist.org/packages/f8projects/laravelcrudgenerator)

## Introduction

This Generator package provides various generators like CRUD, Controller, Model, Migration, View for your painless development of your applications.

## Features

* Easy installations, fast CRUD generation
* Code generator for Model
* Code generator for CRUD Route
* Code generator for Model Migration
* Code generator for CRUD index, create, edit, show views
* Code generator for Layout - Bootstrap 4.2.1
* Code generator for CRUD index, create, edit views
* Code generator for Model store/update request
* Code generator for Model Controller
* Code generator for Controller Repository, RepositoryInterface
* LaravelCollective Forms & HTML ready
* Editable Stubs for easy templating

![Image of Yaktocat](https://f8.lt/wp-content/uploads/laravel-crud-generator-demo.gif)

## Installing into existing Laravel Projects

### Add Packages
```
composer require f8projects/laravelcrudgenerator
```

### Add Service Providers
Add following service providers into your providers array in config/app.php
```
Collective\Html\HtmlServiceProvider::class,
f8projects\laravelcrudgenerator\GeneratorServiceProvider::class,
```

### Add Aliases
```
'Form' => Collective\Html\FormFacade::class,
'Html' => Collective\Html\HtmlFacade::class,
```

### Publish Vendor
```
php artisan vendor:publish --tag=laravelcrudgeneratorstubs
```

## Getting Started

### Generator Commands
```
php artisan crud:generator
```

### Field Inputs
```
name:db_type:html_type
```

* name - name of the field (snake_case recommended)
* db_type - database type. e.g.
  * string - $table->string('field_name')
  * text - $table->text('field_name')
* html_type - html field type for forms. e.g.
  * text
  * textarea

### Examples
user_name,description::textarea,long_description:text:textarea

## Stubs
Files located in resources/laravelcrudgeneratorstubs contains templates you want to modify. Replacing:
```
Test     <-  {{modelName}}
tests    <-  {{modelNamePluralLowerCase}}
Tests    <-  {{modelNamePlural}}
test     <-  {{modelNameSingularLowerCase}}
```
