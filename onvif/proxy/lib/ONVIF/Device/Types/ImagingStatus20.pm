package ONVIF::Device::Types::ImagingStatus20;
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

my %FocusStatus20_of :ATTR(:get<FocusStatus20>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        FocusStatus20
        Extension

    ) ],
    {
        'FocusStatus20' => \%FocusStatus20_of,
        'Extension' => \%Extension_of,
    },
    {
        'FocusStatus20' => 'ONVIF::Device::Types::FocusStatus20',
        'Extension' => 'ONVIF::Device::Types::ImagingStatus20Extension',
    },
    {

        'FocusStatus20' => 'FocusStatus20',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Device::Types::ImagingStatus20

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
ImagingStatus20 from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * FocusStatus20


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Device::Types::ImagingStatus20
   FocusStatus20 =>  { # ONVIF::Device::Types::FocusStatus20
     Position =>  $some_value, # float
     MoveStatus => $some_value, # MoveStatus
     Error =>  $some_value, # string
     Extension =>  { # ONVIF::Device::Types::FocusStatus20Extension
     },
   },
   Extension =>  { # ONVIF::Device::Types::ImagingStatus20Extension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

