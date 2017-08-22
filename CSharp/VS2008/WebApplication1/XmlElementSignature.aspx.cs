﻿using System;
using System.Collections;
using System.Configuration;
using System.Data;
using System.Linq;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Xml.Linq;
using Lacuna.RestPki.Client;
using System.IO;
using Lacuna.RestPki.Api;

namespace WebApplication1 {

	public partial class XmlElementSignature : System.Web.UI.Page {

		public string SignatureFilename { get; private set; }
		public PKCertificate SignerCertificate { get; private set; }

		protected void Page_Load(object sender, EventArgs e) {

			if (!IsPostBack) {

				// Get an instance of the XmlElementSignatureStarter class, responsible for receiving the signature elements and start the
				// signature process
				var signatureStarter = new XmlElementSignatureStarter(Util.GetRestPkiClient());

				// Set the XML to be signed, a sample Brazilian fiscal invoice pre-generated
				signatureStarter.SetXml(Util.GetSampleNFeContent());

				// Set the ID of the element to be signed (this ID is present in the invoice above)
				signatureStarter.SetToSignElementId("NFe35141214314050000662550010001084271182362300");

				// Set the signature policy
				signatureStarter.SetSignaturePolicy(StandardXmlSignaturePolicies.PkiBrazil.NFePadraoNacional);
				// Note: Depending on the signature policy chosen above, setting the security context below may be mandatory (this is not
				// the case for ICP-Brasil policies, which will automatically use the PkiBrazil security context if none is passed)

				// Optionally, set a SecurityContext to be used to determine trust in the certificate chain
				//signatureStarter.SetSecurityContext(new Guid("ID OF YOUR CUSTOM SECURITY CONTEXT"));

				// For instance, to use the test certificates on Lacuna Test PKI (for development purposes only!):
				//signatureStarter.SetSecurityContext(new Guid("803517ad-3bbc-4169-b085-60053a8f6dbf"));

				// Call the StartWithWebPki() method, which initiates the signature. This yields the token, a 43-character
				// case-sensitive URL-safe string, which identifies this signature process. We'll use this value to call the
				// signWithRestPki() method on the Web PKI component (see javascript on the view) and also to complete the signature
				// on the POST action below (this should not be mistaken with the API access token).
				var token = signatureStarter.StartWithWebPki();

				ViewState["Token"] = token;
			}

		}

		protected void SubmitButton_Click(object sender, EventArgs e) {

			// Get an instance of the XmlSignatureFinisher class, responsible for completing the signature process
			var signatureFinisher = new XmlSignatureFinisher(Util.GetRestPkiClient());

			// Set the token for this signature (rendered in a hidden input field, see the view)
			signatureFinisher.SetToken((string)ViewState["Token"]);

			// Call the Finish() method, which finalizes the signature process and returns the signed XML
			var cms = signatureFinisher.Finish();

			// Get information about the certificate used by the user to sign the file. This method must only be called after
			// calling the Finish() method.
			var signerCertificate = signatureFinisher.GetCertificateInfo();

			// At this point, you'd typically store the XML on your database. For demonstration purposes, we'll
			// store the XML on the App_Data folder and render a page with a link to download the CMS and with the
			// signer's certificate details.

			var appDataPath = Server.MapPath("~/App_Data");
			if (!Directory.Exists(appDataPath)) {
				Directory.CreateDirectory(appDataPath);
			}
			var id = Guid.NewGuid();
			var filename = id + ".xml";
			File.WriteAllBytes(Path.Combine(appDataPath, filename), cms);

			this.SignatureFilename = filename;
			this.SignerCertificate = signerCertificate;
			Server.Transfer("XmlElementSignatureInfo.aspx");
		}
	}
}
