package ONVIF::Analytics::Types::SystemCapabilities;
use strict;
use warnings;


__PACKAGE__->_set_element_form_qualified(1);

sub get_xmlns { 'http://www.onvif.org/ver10/schema' };

our $XML_ATTRIBUTE_CLASS;
undef $XML_ATTRIBUTE_CLASS;

sub __get_attr_class {
    return $XML_ATTRIBUTE_CLASS;
}

use Class::Std::Fast::Storable constructor => 'none';
use base qw(SOAP::WSDL::XSD::Typelib::ComplexType);

Class::Std::initialize();

{ # BLOCK to scope variables

my %DiscoveryResolve_of :ATTR(:get<DiscoveryResolve>);
my %DiscoveryBye_of :ATTR(:get<DiscoveryBye>);
my %RemoteDiscovery_of :ATTR(:get<RemoteDiscovery>);
my %SystemBackup_of :ATTR(:get<SystemBackup>);
my %SystemLogging_of :ATTR(:get<SystemLogging>);
my %FirmwareUpgrade_of :ATTR(:get<FirmwareUpgrade>);
my %SupportedVersions_of :ATTR(:get<SupportedVersions>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        DiscoveryResolve
        DiscoveryBye
        RemoteDiscovery
        SystemBackup
        SystemLogging
        FirmwareUpgrade
        SupportedVersions
        Extension

    ) ],
    {
        'DiscoveryResolve' => \%DiscoveryResolve_of,
        'DiscoveryBye' => \%DiscoveryBye_of,
        'RemoteDiscovery' => \%RemoteDiscovery_of,
        'SystemBackup' => \%SystemBackup_of,
        'SystemLogging' => \%SystemLogging_of,
        'FirmwareUpgrade' => \%FirmwareUpgrade_of,
        'SupportedVersions' => \%SupportedVersions_of,
        'Extension' => \%Extension_of,
    },
    {
        'DiscoveryResolve' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'DiscoveryBye' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'RemoteDiscovery' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'SystemBackup' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'SystemLogging' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'FirmwareUpgrade' => 'SOAP::WSDL::XSD::Typelib::Builtin::boolean',
        'SupportedVersions' => 'ONVIF::Analytics::Types::OnvifVersion',
        'Extension' => 'ONVIF::Analytics::Types::SystemCapabilitiesExtension',
    },
    {

        'DiscoveryResolve' => 'DiscoveryResolve',
        'DiscoveryBye' => 'DiscoveryBye',
        'RemoteDiscovery' => 'RemoteDiscovery',
        'SystemBackup' => 'SystemBackup',
        'SystemLogging' => 'SystemLogging',
        'FirmwareUpgrade' => 'FirmwareUpgrade',
        'SupportedVersions' => 'SupportedVersions',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Analytics::Types::SystemCapabilities

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
SystemCapabilities from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * DiscoveryResolve


=item * DiscoveryBye


=item * RemoteDiscovery


=item * SystemBackup


=item * SystemLogging


=item * FirmwareUpgrade


=item * SupportedVersions


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Analytics::Types::SystemCapabilities
   DiscoveryResolve =>  $some_value, # boolean
   DiscoveryBye =>  $some_value, # boolean
   RemoteDiscovery =>  $some_value, # boolean
   SystemBackup =>  $some_value, # boolean
   SystemLogging =>  $some_value, # boolean
   FirmwareUpgrade =>  $some_value, # boolean
   SupportedVersions =>  { # ONVIF::Analytics::Types::OnvifVersion
     Major =>  $some_value, # int
     Minor =>  $some_value, # int
   },
   Extension =>  { # ONVIF::Analytics::Types::SystemCapabilitiesExtension
     HttpFirmwareUpgrade =>  $some_value, # boolean
     HttpSystemBackup =>  $some_value, # boolean
     HttpSystemLogging =>  $some_value, # boolean
     HttpSupportInformation =>  $some_value, # boolean
     Extension =>  { # ONVIF::Analytics::Types::SystemCapabilitiesExtension2
     },
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

