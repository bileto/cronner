# Collections

## Description

Simple tool which helps with maintenance of cron tasks.

It requires **PHP >= 5.3.0** and **Nette Framework >= 2.0.***.

## Usage

It is very simple to use it because configuration is only in method annotations. Example class follows:

```php
class CronTasks extends Tasks {
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
```

using configuration

```neon
services:
    cronner: stekycz\Cronner\Cronner
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

