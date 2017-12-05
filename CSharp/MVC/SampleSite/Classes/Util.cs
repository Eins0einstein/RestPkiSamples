﻿using Lacuna.RestPki.Api;
using Lacuna.RestPki.Client;
using System;
using System.Collections.Generic;
using System.Configuration;
using System.IO;
using System.Linq;
using System.Web;

namespace SampleSite.Classes {

	public class Util {

		public static RestPkiClient GetRestPkiClient() {
			var accessToken = ConfigurationManager.AppSettings["RestPkiAccessToken"];
			if (string.IsNullOrEmpty(accessToken) || accessToken.Contains(" API ")) {
				throw new Exception("The API access token was not set! Hint: to run this sample you must generate an API access token on the REST PKI website and paste it on the web.config file");
			}
			var endpoint = ConfigurationManager.AppSettings["RestPkiEndpoint"];
			if (string.IsNullOrEmpty(endpoint)) {
				endpoint = "https://pki.rest/";
			}
			return new RestPkiClient(endpoint, accessToken);
		}

		public static string ContentPath {
			get {
				return HttpContext.Current.Server.MapPath("~/Content");
			}
		}

		public static byte[] GetPdfStampContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "PdfStamp.png"));
		}

		public static byte[] GetSampleDocContent() {
			return File.ReadAllBytes(GetSampleDocPath());
		}

		public static string GetSampleDocPath() {
			return Path.Combine(ContentPath, "01.pdf");
		}

		public static byte[] GetBatchDocContent(int id) {
			return File.ReadAllBytes(Path.Combine(ContentPath, string.Format("{0:D2}.pdf", ((id - 1) % 10) + 1)));
		}

		public static byte[] GetSampleNFeContent() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "SampleNFe.xml"));
		}

		public static byte[] GetSampleXmlDocument() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "SampleDocument.xml"));
		}

		public static byte[] GetXmlInvoiceWithSigs() {
			return File.ReadAllBytes(Path.Combine(ContentPath, "InvoiceWithSigs.xml"));
		}
	}
}
