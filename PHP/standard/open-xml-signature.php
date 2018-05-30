<?php
/*
 * This file submits a Xml file to Rest PKI for inspection and renders the results.
 */

require __DIR__ . '/vendor/autoload.php';

use Lacuna\RestPki\StandardSignaturePolicies;
use Lacuna\RestPki\XmlSignatureExplorer;

// Our demo only works if a userfile is given to work with
$userfile = isset($_GET['userfile']) ? $_GET['userfile'] : null;
if (empty($userfile)) {
    throw new \Exception("No file was uploaded");
}

// Get an instance of the XmlSignatureExplorer class, used to open/validate XML signatures
$sigExplorer = new XmlSignatureExplorer(getRestPkiClient());

// Set the XML file
$sigExplorer->setSignatureFileFromPath("app-data/{$userfile}");

// Specify that we want to validate the signatures in the file, not only inspect them
$sigExplorer->validate = true;

// Accept any valid XmlDSig signature as long as the signer has an ICP-Brasil certificate as long as the signer is
// trusted by the security context.
$sigExplorer->defaultSignaturePolicy = StandardSignaturePolicies::XML_DSIG_BASIC;

// Specify the security context. We have encapsulated the security context choice on util.php.
$sigExplorer->securityContext = getSecurityContextId();

// Call the open() method, which returns the signature file's information
$signatures = $sigExplorer->open();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Open existing signatures on an existing XML</title>
    <?php include 'includes.php' // jQuery and other libs (used only to provide a better user experience, but NOT
    // required to use the Web PKI component) ?>
</head>
<body>

<?php include 'menu.php' // The top menu, this can be removed entirely ?>

<div class="container">

    <h2>Open/validate signatures on an existing XML file</h2>

    <h3>The given file contains <?= count($signatures) ?> signatures:</h3>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">

        <?php for ($i = 0; $i < count($signatures); $i++) {

            $signature = $signatures[$i];
            $collapseId = "signer_" . $i . "_collapse";
            $headingId = "signer_" . $i . "_heading";

            ?>

            <div class="panel panel-default">
                <div class="panel-heading" role="tab" id="<?= $headingId ?>">
                    <h4 class="panel-title">
                        <a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion"
                           href="#<?= $collapseId ?>" aria-expanded="true" aria-controls="<?= $collapseId ?>">
                            <?= $signature->certificate->subjectName->commonName ?>
                            <?php if ($signature->validationResults != null) { ?>
                                <text>-</text>
                                <?php if ($signature->validationResults->isValid()) { ?>
                                    <span style="color: green; font-weight: bold;">valid</span>
                                <?php } else { ?>
                                    <span style="color: red; font-weight: bold;">invalid</span>
                                <?php } ?>
                            <?php } ?>
                        </a>
                    </h4>
                </div>
                <div id="<?= $collapseId ?>" class="panel-collapse collapse" role="tabpanel"
                     aria-labelledby="<?= $headingId ?>">
                    <div class="panel-body">
                        <p>Type: <?= $signature->type ?></p>
                        <?php if ($signature->signedElement != null) { ?>
                            <p>
                                Signed element: <?= $signature->signedElement->localName ?>
                                <?php if ($signature->signedElement->namespaceUri != null) { ?>
                                    <text>(xmlns: <?= $signature->signedElement->namespaceUri ?>)</text>
                                <?php } ?>
                            </p>
                        <?php } ?>
                        <?php if ($signature->signingTime != null) { ?>
                            <p>Signing time: <?= date('d/m/Y H:i', strtotime($signature->signingTime)) ?></p>
                        <?php } ?>
                        <?php if ($signature->signaturePolicy != null) { ?>
                            <p>Signature policy: <?= $signature->signaturePolicy->oid ?></p>
                        <?php } ?>
                        <p>
                            Signer information:
                        <ul>
                            <li>Subject: <?= $signature->certificate->subjectName->commonName ?></li>
                            <li>Email: <?= $signature->certificate->emailAddress ?></li>
                            <li>
                                ICP-Brasil fields
                                <ul>
                                    <li>Tipo de
                                        certificado: <?= $signature->certificate->pkiBrazil->certificateType ?></li>
                                    <li>CPF: <?= $signature->certificate->pkiBrazil->cpf ?></li>
                                    <li>Responsavel: <?= $signature->certificate->pkiBrazil->responsavel ?></li>
                                    <li>Empresa: <?= $signature->certificate->pkiBrazil->companyName ?></li>
                                    <li>CNPJ: <?= $signature->certificate->pkiBrazil->cnpj ?></li>
                                    <li>
                                        RG: <?= $signature->certificate->pkiBrazil->rgNumero . " " . $signature->certificate->pkiBrazil->rgEmissor . " " . $signature->certificate->pkiBrazil->rgEmissorUF ?></li>
                                    <li>
                                        OAB: <?= $signature->certificate->pkiBrazil->oabNumero . " " . $signature->certificate->pkiBrazil->oabUF ?></li>
                                </ul>
                            </li>
                        </ul>
                        </p>
                        <?php if ($signature->validationResults != null) { ?>
                            <p>Validation results:<br/>
                                <textarea style="width: 100%" rows="20"><?= $signature->validationResults ?></textarea>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

</body>
</html>
