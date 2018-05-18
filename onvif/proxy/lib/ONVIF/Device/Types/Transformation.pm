package ONVIF::Device::Types::Transformation;
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

my %Translate_of :ATTR(:get<Translate>);
my %Scale_of :ATTR(:get<Scale>);
my %Extension_of :ATTR(:get<Extension>);

__PACKAGE__->_factory(
    [ qw(        Translate
        Scale
        Extension

    ) ],
    {
        'Translate' => \%Translate_of,
        'Scale' => \%Scale_of,
        'Extension' => \%Extension_of,
    },
    {
        'Translate' => 'ONVIF::Device::Types::Vector',
        'Scale' => 'ONVIF::Device::Types::Vector',
        'Extension' => 'ONVIF::Device::Types::TransformationExtension',
    },
    {

        'Translate' => 'Translate',
        'Scale' => 'Scale',
        'Extension' => 'Extension',
    }
);

} # end BLOCK








1;


=pod

=head1 NAME

ONVIF::Device::Types::Transformation

=head1 DESCRIPTION

Perl data type class for the XML Schema defined complexType
Transformation from the namespace http://www.onvif.org/ver10/schema.






=head2 PROPERTIES

The following properties may be accessed using get_PROPERTY / set_PROPERTY
methods:

=over

=item * Translate


=item * Scale


=item * Extension




=back


=head1 METHODS

=head2 new

Constructor. The following data structure may be passed to new():

 { # ONVIF::Device::Types::Transformation
   Translate => ,
   Scale => ,
   Extension =>  { # ONVIF::Device::Types::TransformationExtension
   },
 },




=head1 AUTHOR

Generated by SOAP::WSDL

=cut

