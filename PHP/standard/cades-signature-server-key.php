<?php

/*
 * This file performs a CAdES signature using REST PKI and a PKCS #12 file located on the server-side.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\CadesSignatureStarter;
use Lacuna\RestPki\StandardSecurityContexts;
use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\CadesSignatureFinisher2;

// Read the PKCS #12 file
if (!$certStore = file_get_contents("content/Pierre de Fermat.pfx")) {
    throw new \Exception("Unable to read PKCS #12 file");
}
if (!openssl_pkcs12_read($certStore, $certObj, "1234")) {
    throw new \Exception("Unable to open the PKCS #12 file");
}

$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
$cmsfile = isset($_GET['cmsfile']) ? $_GET['cmsfile'] : null;

// Instantiate the CadesSignatureStarter class, responsible for receiving the signature elements and start the signature
// process.
$signatureStarter = new CadesSignatureStarter(getRestPkiClient());

// Set the signer certificate.
$signatureStarter->setSignerCertificateRaw($certObj['cert']);

if (!empty($userfile)) {

    // If the URL argument "userfile" is filled, it means the user was redirected here by the file upload.php (signature
    // with file uploaded by user). We'll set the path of the file to be signed, which was saved in the "app-data" folder
    // by upload.php.
    $signatureStarter->setFileToSignFromPath("app-data/{$userfile}");

} elseif (!empty($cmsfile)) {

    /*
     * If the URL argument "cmsfile" is filled, the user has asked to co-sign a previously signed CMS. We'll set the
     * path to the CMS to be co-signed, which was previously saved in the "app-data" folder by the file
     * cades-signature-action.php. Note two important things:
     *
     * 1. The CMS to be co-signed must be set using the method "setCmsToSign" or "setCmsFileToSign", not the method
     *    "setContentToSign" nor "setFileToSign".
     *
     * 2. Since we're creating CMSs with encapsulated content (see call to setEncapsulateContent below), we don't need
     *    to set the content to be signed, REST PKI will get the content from the CMS being co-signed.
     */
    $signatureStarter->setCmsToCoSignFromPath("app-data/{$cmsfile}");

} else {

    // If both userfile and cmsfile are null, this is the "signature with server file" case. We'll set the path to
    // the sample document.
    $signatureStarter->setFileToSignFromPath('content/SampleDocument.pdf');

}

// Set the signature policy.
$signatureStarter->signaturePolicy = StandardSignaturePolicies::CADES_ICPBR_ADR_BASICA;

// For this sample, we'll use the Lacuna Test PKI as our security context in order to accept our test certificate used
// above ("Pierre de Fermat"). This security context should be used ***** FOR DEVELOPMENT PUPOSES ONLY *****
$signatureStarter->securityContext = StandardSecurityContexts::LACUNA_TEST;

// Optionally, set whether the content should be encapsulated in the resulting CMS. If this parameter is omitted,
// the following rules apply:
// - If no CmsToSign is given, the resulting CMS will include the content
// - If a CmsToCoSign is given, the resulting CMS will include the content if and only if the CmsToCoSign also includes
//   the content
$signatureStarter->encapsulateContent = true;

// Call the start() method, which initiates the signature. This yields the parameters for the signature using the
// certificate
$signatureParams = $signatureStarter->start();

// Perform the signature using the parameters returned by Rest PKI with the key extracted from PKCS #12
openssl_sign($signatureParams->toSignData, $signature, $certObj['pkey'], $signatureParams->openSslSignatureAlgorithm);

// Instantiate the CadesSignatureFinisher2 class, responsible for completing the signature process
$signatureFinisher = new CadesSignatureFinisher2(getRestPkiClient());

// Set the token
$signatureFinisher->token = $signatureParams->token;

// Set the signature
$signatureFinisher->setSignatureRaw($signature);

// Call the finish() method, which finalizes the signature process and returns a SignatureResult object
$signatureResult = $signatureFinisher->finish();

// The "certificate" property of the SignatureResult object contains information about the certificate used by the user
// to sign the file.
$signerCert = $signatureResult->certificate;

// At this point, you'd typically store the CMS on your database. For demonstration purposes, we'll
// store the CMS on a temporary folder publicly accessible and render a link to it.

createAppData(); // make sure the "app-data" folder exists (util.php)
$filename = uniqid() . ".p7s";

// The SignatureResult object has functions for writing the signature file to a local file (writeToFile()) and to get
// its raw contents (getContent()). For large files, use writeToFile() in order to avoid memory allocation issues.
$signatureResult->writeToFile("app-data/{$filename}");

?><!DOCTYPE html>
<html>
<head>
    <title>CAdES Signature</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>CAdES Signature</h2>

    <p>File signed successfully!</p>

    <p>
        Signer information:
    <ul>
        <li>Subject: <?= $signerCert->subjectName->commonName ?></li>
        <li>Email: <?= $signerCert->emailAddress ?></li>
        <li>
            ICP-Brasil fields
            <ul>
                <li>Tipo de certificado: <?= $signerCert->pkiBrazil->certificateType ?></li>
                <li>CPF: <?= $signerCert->pkiBrazil->cpf ?></li>
                <li>Responsavel: <?= $signerCert->pkiBrazil->responsavel ?></li>
                <li>Empresa: <?= $signerCert->pkiBrazil->companyName ?></li>
                <li>CNPJ: <?= $signerCert->pkiBrazil->cnpj ?></li>
                <li>
                    RG: <?= $signerCert->pkiBrazil->rgNumero . " " . $signerCert->pkiBrazil->rgEmissor . " " . $signerCert->pkiBrazil->rgEmissorUF ?></li>
                <li>OAB: <?= $signerCert->pkiBrazil->oabNumero . " " . $signerCert->pkiBrazil->oabUF ?></li>
            </ul>
        </li>
    </ul>
    </p>

    <h3>Actions:</h3>
    <ul>
        <li><a href="app-data/<?= $filename ?>">Download the signed file</a></li>
        <li><a href="open-cades-signature.php?userfile=<?= $filename ?>">Open/validate the signed file</a></li>
    </ul>

</div>

</body>
</html>
