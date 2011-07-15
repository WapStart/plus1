#!/usr/bin/perl

use URI::Escape;
use HTTP::Request;
use LWP::UserAgent;
use Digest::SHA1;
use CGI::Cookie;

use constant COOKIE_NAME => 'wssid';
use constant VERSION => 2;

my %plus1Hash;

$plus1Hash{'version'} = 2;
$plus1Hash{'markup'} = 3 if (!exists($plus1Hash{'markup'}));
$plus1Hash{'id'} = 4245 if (!exists($plus1Hash{'site'}));

#$plus1Hash{'sex'} = '<sex here>';
#$plus1Hash{'age'} = '<age here>';
#$plus1Hash{'geoData'} = '<geo data here>';

## if exists authorized user set login
#$plus1Hash{'login'} = '<login here>';

# global per page id
if (!exists($plus1Hash{'pageId'})) {
	my $ctx = Digest::SHA1->new;
	$ctx->add(int(rand(1000000)+time()) + 1);

	$plus1Hash{'pageId'} = $ctx->hexdigest;
}

my @knownHeaderList  = (
	'REMOTE_ADDR',
	'HTTP_USER_AGENT',
	'HTTP_HOST',
	'HTTP_REFERER',
	'HTTP_VIA'	
);

my @headerList;

foreach $key (keys %ENV) {
	if (
		(grep $_ eq $key, @knownHeaderList)
		|| (index($key, 'HTTP_ACCEPT_') != -1)
		|| (index($key, 'HTTP_X_') != -1)
	) {
		my $temp = $key;
		$temp =~ s/_/-/g;
		
		push(@headerList, 'x-plus-'.lc($temp).": ".$ENV{$key});
	}
}

# client session
my %cookies = CGI::Cookie->fetch;

if (exists($cookies{COOKIE_NAME()})) {
	$plus1Hash{'clientSession'} = $cookies{COOKIE_NAME()}->value;
} else {
	my $session = Digest::SHA1->new;
	$session->add(int(rand(1000000)+time())+1);

	$plus1Hash{'clientSession'} = $session->hexdigest;

	my $cookie = 
		CGI::Cookie->new(
			-name => COOKIE_NAME, 
			-value => $plus1Hash{'clientSession'},
			-expires => '+12M'
		);

	print "Set-Cookie: ".$cookie->as_string."\n";
}

my $plus1Url = "http://ro.trunk.plus1.oemtest.ru/?tplVersion=2";

if (exists($ENV{"REMOTE_ADDR"}) {
	$plus1Url .= "&ip=".$ENV{"REMOTE_ADDR"};
}

if (exists($ENV{"HTTP_X_FORWARDED_FOR"}) {
	$plus1Url .= "&xfip=".$ENV{"HTTP_X_FORWARDED_FOR"};
}

# fetch banner
while (my ($key, $value) = each(%plus1Hash) ) {
	$plus1Url .= ("&".$key ."=".uri_escape($value));
}

my $ua = LWP::UserAgent->new;

if (defined($ENV{"HTTP_USER_AGENT"})) {
	$ua->agent($ENV{"HTTP_USER_AGENT"});
}

my $plus1Response = $ua->get($plus1Url, @headerList);

print "Content-Type: text/html; charset=UTF-8\n\n";

if (
	$plus1Response->is_success
	&& $plus1Response->content =~ m/<!-- i4jgij4pfd4ssd -->/
) {
	print $plus1Response->content;
}
