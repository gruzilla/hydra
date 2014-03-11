'strict';

var casper = require('casper').create({
    page: {
        customHeaders: {
            'Accept-Language': 'en-us;q=0.5,en;q=0.3'
        }
    }
});

if (casper.cli.args.length !== 3) {
    casper.die('USAGE: casperjs login.js URL USERNAME PASSWORD');
}

var url = casper.cli.args[0];
var username = casper.cli.args[1];
var password = casper.cli.args[2];

casper.start(url);

casper.then(function() {
    if (!this.exists('form[id="oauth_form"]')) {
        this.echo('No form found. Token may not be valid.', 'ERROR');
    } else {
        this.fill(
            'form[id="oauth_form"]',
            {
                'session[username_or_email]': username,
                'session[password]': password
            },
            true
        );
    }
});

casper.then(function(response) {
    if (this.exists('.error')) {
        this.echo(this.fetchText('.error'));
        return;
    }

    if (response.headers.get('X-Hydra')) {
        this.debugPage();
        return;
    }

    if (this.exists('.callback a')) {
        var redirect = this.getElementAttribute('.callback a', 'href');

        this.echo('redirecting to ' + redirect, 'INFO');
        casper.open(redirect).then(function() {
            this.debugPage();
        });
    } else {
        this.echo('landed on wrong page.', 'ERROR');
        require('utils').dump(response);
        this.debugHTML();
    }
});

casper.run();
