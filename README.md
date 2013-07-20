NATS mailer
========

php/python mailer app for toomuchmedia.com NATS software.
Allows to send mass mail for users or webmasters of NATS with given template.

Frontend is writen with help of my own crappy framework. Mailing provided with custom (also!) multithreaded python app.
Monitoring of mailing is done by exchanging data between python and php using memcache.

Reasonably good example of how to NOT write web apps (no comments, OOP for namespacing, app wide variables and so on)
