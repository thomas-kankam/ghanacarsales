<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dealer account onboarded successfully - OmniCarsGH</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body style="margin: 0; padding: 0; background-color: #e6e6e6; font-family: 'Poppins', Arial, sans-serif;">
    <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#e6e6e6">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
                    style="max-width: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td align="center" bgcolor="#141414" style="padding: 30px 20px;">
                            <img src="https://omnicarsgh.com/Images/OmniLogoWhite.png" alt="OmniCarsGH"
                                style="max-width: 140px; margin-bottom: 15px;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 25px; background-color: #fdfdfd;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
                                style="border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <p style="font-size: 16px; color: #333333; margin-bottom: 12px;">
                                            Hello
                                            <span
                                                style="display: inline-block; background-color: #f4fff0; padding: 6px 10px; border-radius: 6px; color: #62a93b; font-weight: 600; font-size: 16px; border: 1px solid #bcffb3;">
                                                {{ $dealerName }}
                                            </span>,
                                        </p>
                                        <p style="font-size: 15px; color: #555; margin: 0;">{{ $body }}</p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <p style="font-size: 14px; color: #888; text-align: center; margin: 0;">
                                            You can now continue using your dealer account on OmniCarsGH.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellspacing="0" cellpadding="0" border="0"
                                style="border-top: 1px solid #eee; margin-top: 25px;">
                                <tr>
                                    <td align="center" style="padding-top: 20px;">
                                        <p style="margin: 0 0 5px; color: #aaa; font-size: 12px;">Best regards, <br> The
                                            OmniCarsGH Team</p>
                                        <p style="margin: 0; color: #000; font-size: 12px; font-weight: bold;">
                                            {{ config('app.name') }} · ©
                                            {{ date('Y') }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
