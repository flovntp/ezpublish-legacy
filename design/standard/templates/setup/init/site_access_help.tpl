<h3>{"URL"|i18n('design/standard/setup/init')}</h3>
{"The path determines access."|i18n('design/standard/setup/init')}
<p>{"e.g. %adminsite and %usersite"|i18n('design/standard/setup/init',,hash('%adminsite',concat('<nobr>http://ez.no/<b>admin</b></nobr>'),
                                                                            '%usersite',concat('<nobr>http://ez.no/<b>web</b></nobr>')))}</p>

<h3>{"Port"|i18n('design/standard/setup/init')}</h3>
{"The port number determines access.*"|i18n('design/standard/setup/init')}
<p>{"e.g. %adminsite and %usersite"|i18n('design/standard/setup/init',,hash('%adminsite',concat('<nobr>http//ez.no:<b>81</b></nobr>'),
                                                                            '%usersite',concat('<nobr>http://ez.no:<b>80</b></nobr>')))}</p>
<p>{"* This requires that your web server is setup to handle the port numbers."|i18n('design/standard/setup/init')}</p>

<h3>{"Hostname"|i18n('design/standard/setup/init')}</h3>
{"The hostname determines access.*"|i18n('design/standard/setup/init')}
<p>{"e.g. %adminsite and %usersite"|i18n('design/standard/setup/init',,hash('%adminsite',concat('<nobr>http//<b>admin.ez.no</b></nobr>'),
                                                                            '%usersite',concat('<nobr>http://<b>www.ez.no</b></nobr>')))}</p>
<p>{"* This requires that you have a DNS setup for your hostname."|i18n('design/standard/setup/init')}</p>
<p/>
<p>{"For more detailed information on site access see the %1"|i18n("design/standard/setup/init",,array(concat( '<a href="http://ez.no/developer/ez_publish_3/documentation/installation_and_configuration/configuration/site_access" target="_blank">', "online documentation"|i18n("design/standard/setup/init",'site access documentation link'), '</a>' ) ) )}</p>
