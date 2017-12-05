Test certificates
=================

If you need test certificates to use in a development or staging environment, you can
use one of the certificates in our test PKI.

**NOTICE: The Lacuna Test PKI should never be trusted in a production environment**

First, you need to specify in your API calls that the Lacuna Test PKI security context
is to be trusted (naturally, it is not trusted by default). The Lacuna Test PKI security context ID is:

	Lacuna Test PKI security context ID (for development purposes only!!!):
	803517ad-3bbc-4169-b085-60053a8f6dbf

Where you'll use it depends on the programming language you're using and on the operation you're
performing. For instance, if you are performing a PAdES (PDF) signature on PHP, edit the file pades-signature.php:

    // Trust Lacuna Test PKI (for development purposes only!!!)
    $signatureStarter->setSecurityContext("803517ad-3bbc-4169-b085-60053a8f6dbf");
    
Or if you're using Java, edit the file PadesSignatureController.java:

    // Trust Lacuna Test PKI (for development purposes only!!!)
    signatureStarter.setSecurityContext(new SecurityContext("803517ad-3bbc-4169-b085-60053a8f6dbf"));
    
Whatever operation or language you're using, it should be fairly clear from the code comments where to put the security context ID.

From then on, the certificates in our test PKI will be trusted. Download the file [TestCertificates.zip](TestCertificates.zip?raw=true) to get the certificates. All files are PKCS #12 certificates with password **1234**. The following certificates are included:

* Alan Mathison Turing
    * Email: testturing@lacunasoftware.com
    * ICP-Brasil mock certificate with CPF 56072386105
* Ferdinand Georg Frobenius
    * Email: testfrobenius@lacunasoftware.com
    * ICP-Brasil mock certificate with CPF 87378011126
* Pierre de Fermat
    * Email: test@lacunasoftware.com
    * ICP-Brasil mock certificate with CPF 47363361886
* Wayne Enterprises, Inc 
	* Email: test@wayneenterprises.com
    * ICP-Brasil mock certificate with CNPJ 34785515000166	
	* Legal representative: Bruce Wayne mock certificate with CPF 47891178631
	

If you need a certificate with a particular information, [contact us](http://support.lacunasoftware.com/).

Always remember to **remove the trust in the Lacuna Test PKI security context when you're moving to a production environment**. Better yet, use some sort of conditional compilation so that the test PKI is only trusted when running in debug mode.
