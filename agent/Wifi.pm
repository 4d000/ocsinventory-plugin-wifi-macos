package Ocsinventory::Agent::Modules::Wifi;

use strict;
use warnings;

sub new {
    my $name = "wifi";

    my (undef, $context) = @_;
    my $self = {};

    $self->{logger} = new Ocsinventory::Logger({
        config => $context->{config}
    });

    $self->{logger}->{header} = "[$name]";
    $self->{context} = $context;

    $self->{structure} = {
        name => $name,
        inventory_handler => "${name}_inventory_handler",
        start_handler => undef,
        prolog_writer => undef,
        prolog_reader => undef,
        end_handler => undef,
    };

    bless $self;
}

sub wifi_inventory_handler {
    my $self = shift;
    my $logger = $self->{logger};
    my $common = $self->{context}->{common};

    $logger->debug("Entering wifi_inventory_handler");

    my ($ssid, $ip, $mac, $interface) = ('', '', '', '');

    # Find the hardware port for Wi-Fi
    open my $hw, '-|', 'networksetup -listallhardwareports 2>/dev/null';
    if ($hw) {
        my $found_wifi = 0;
        while (<$hw>) {
            if (/^Hardware Port: Wi-Fi$/) {
                $found_wifi = 1;
            } elsif ($found_wifi && /^Device: (.+)$/) {
                $interface = $1;
                last;
            }
        }
        close $hw;
    }

    if (!$interface) {
        $logger->error("Could not determine Wi-Fi interface");
        return;
    }

    $logger->debug("Detected Wi-Fi interface: $interface");

    # Get SSID using system_profiler
    open my $sp, '-|', 'system_profiler SPAirPortDataType 2>/dev/null';
    if ($sp) {
        while (<$sp>) {
            if (/Current Network Information:/) {
                my $next_line = <$sp>;
                if (defined $next_line) {
                    $next_line =~ s/^\s+//;
                    $next_line =~ s/:$//;
					$next_line=~ s/\s+$//;
                    $ssid = $next_line;
                    last;
                }
            }
        }
        close $sp;
    } else {
        $logger->error("Failed to open system_profiler");
    }

    # Get IP address
    $ip = `ipconfig getifaddr $interface 2>/dev/null`;
    chomp $ip;

    # Get MAC address
    $mac = `ifconfig $interface 2>/dev/null | awk '/ether/ { print \$2; exit }'`;
    chomp $mac;

    # Logging
    $logger->debug("SSID: $ssid");
    $logger->debug("IP: $ip");
    $logger->debug("MAC: $mac");

    # Push to XML
    push @{$common->{xmltags}->{WIFI}}, {
        SSID => [$ssid],
        IP   => [$ip],
        MAC  => [$mac],
    };
}

1;
