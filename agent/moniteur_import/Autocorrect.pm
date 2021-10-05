#!/usr/sbin/perl
package Autocorrect;
use Text::Unaccent;
use Encode;

sub new
    {
    my $class  = shift;
    my $lang      = shift;
    my $lang_file = shift;
    my %dict; 

    open F, $lang_file;
    while (<F>) { 
        $dict{$_}++ for (lc =~ /[a-z\-]+/g); 
        }

    my $self   = {
        _dict => \%dict,
        _lang => $lang,
        _file => $lang_file
        };
    bless $self, $class;
    return $self;
    }


sub correct_short {
    my ($self,$word) = @_;
    $NWORDS = $self->{_dict};
    return $word if $NWORDS->{$word};
    for (&edits1($word)) 
        {
        if ($NWORDS->{$_} > $NWORDS->{$word})
            {
            $word = $_;
            }
        }
    $word;
    }

sub correct_long {
    my ($self,$word) = @_;
    $NWORDS = $self->{_dict};
    return $word if $NWORDS->{$word};
    for (&edits1($word), &edits2($word)) 
        {
        if ($NWORDS->{$_} > $NWORDS->{$word})
            {
            $word = $_;
            }
        }
    $word;
    }
 
sub normalize
    {
    my $txt = shift;
	return '' if ($txt eq '');
	$txt_raw = Encode::is_utf8($txt) ? $txt : decode("ISO_8859-1",$txt);
	$txt_utf16 = encode("UTF16",$txt_raw);
	$txt_unac = unac_string_utf16($txt_utf16);
    $txt = decode("UTF16",$txt_unac);
    $txt = lc($txt);
    return $txt;
    }

sub edits1 {
     $word = shift;
     &dedup(map { ($a, $b) = @$_;
	          ( $a . substr($b, 1),
	            $a . substr($b, 1, 1) . substr($b, 0, 1) . substr($b, 2),
	            map { ($a . $_ . substr($b,1), $a . $_ . $b) } 'a'..'z' )
       } map { [substr($word, 0, $_), substr($word, $_)] } 0..length($word)-1);
   }

sub dedup { %h = map { $_, 1 } @_; keys %h }

sub edits2 { &dedup(map { &edits1($_) } &edits1(shift)) }


1;
