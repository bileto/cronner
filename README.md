# Cronner [![Build Status](https://travis-ci.org/stekycz/Cronner.png?branch=master)](https://travis-ci.org/stekycz/Cronner)

- [Description](#description)
- [Usage](#usage)
- [Annotations](#annotations)
- [Author](#author)
- [License](#license)

## Description

Simple tool which helps with maintenance of cron tasks.

It requires **PHP >= 5.3.0** and **Nette Framework >= 2.0.***.

## Usage

It is very simple to use it because configuration is only in method annotations. Example class follows:

```php
class CronTasks implements \stekycz\Cronner\ITasksContainer {
    /**
     * @cronner-task E-mail sending
     * @cronner-period 1 day
     * @cronner-days working days
     * @cronner-time 23:30 - 05:00
     */
    public function sendEmails() {
        // Code which sends all your e-mails
    }

    /**
     * @cronner-task Important data replication
     * @cronner-period 3 hours
     */
    public function replicateImportantData() {
        // Replication code
    }
}
```

Then you can use it very easily in `Presenter`

```php
class CronPresenter extends \Nette\Application\UI\Presenter {
    private $cronner;

    public function injectCronner(Cronner $cronner) {
        $this->cronner = $cronner;
    }

    public function actionCron() {
        $this->cronner->addTasksCallback(function () {
            // Some magic code can be here :-)
            return new CronTasks();
        });
        $this->cronner->run();
    }
}
```

using service configuration

```neon
services:
    cronner: stekycz\Cronner\Cronner(stekycz\Cronner\TimestampStorage\FileStorage(%wwwDir%/../temp/cronner))
```

or using compiler extension

```neon
cronner:
    timestampStorage: stekycz\Cronner\TimestampStorage\FileStorage(%wwwDir%/../temp/cronner)
```

If you want to use Compiler Extension then do not forgot add following code to `bootstrap.php`.

```php
stekycz\Cronner\DI\CronnerExtension::register($configurator);
```

## Annotations

### @cronner-task

This annotations is **required for all** public methods which should be used as a task.
Its value is used as a name of task. If value is missing the name is build from class name
and method name.

If this annotation is single (for Cronner) in task method comment then the task is runned
everytime when Cronner runs.

#### Example

```php
/**
 * @cronner-task Fetches all public data from all registered social networks
 */
```

### @cronner-period

Not required but recomanded annotation which specifies period of task execution.
The period is minimal time between two executions of the task. It's value can be
anything what is acceptable for `strtotime()` method.

**Attention!** The value of this annotation must not contain any sign (+ or -).

#### Example

```php
/**
 * @cronner-period 1 day
 */
```

### @cronner-days

Allows run the task only on specified days. Possible values are abbreviations of week day names.
It means `Mon`, `Tue`, `Wed`, `Thu`, `Fri`, `Sat` and `Sun`. For simplier usage there are two shortcuts:
`working days` (`Mon`, `Tue`, `Wed`, `Thu`, `Fri`) and `weekend` (`Sat` and `Sun`) which are internaly
expanded to specific days. Multiple values must be separated by comma.

#### Example

```php
/**
 * @cronner-days working days, Sun
 */
```

### @cronner-time

Specifies day time range (or ranges) in which the task can be run. It can be range or a specific minute.
It uses 24 hour time model. Multiple values must be separated by comma.

The time can be defined over midnight as it is in following example.

#### Example

```php
/**
 * @cronner-time 11:00, 23:30 - 05:00
 */
```

## Author

My name is Martin Štekl. Feel free to contact me on [e-mail](mailto:martin.stekl@gmail.com)
or follow me on [Twitter](https://twitter.com/stekycz).

## License

Copyright (c) 2013 Martin Štekl <martin.stekl@gmail.com>

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

