import React, { useState } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import * as OTPAuth from 'otpauth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/use-toast';

const TwoFactorSetup = () => {
  const [secret] = useState(() => {
    const buffer = new Uint8Array(20);
    crypto.getRandomValues(buffer);
    return new OTPAuth.Secret({ buffer });
  });
  const [verificationCode, setVerificationCode] = useState('');
  const [isConfigured, setIsConfigured] = useState(false);
  const { toast } = useToast();

  // Generate TOTP URI for QR code
  const totp = new OTPAuth.TOTP({
    issuer: 'Demo 2FA App',
    label: 'user@example.com',
    algorithm: 'SHA1',
    digits: 6,
    period: 30,
    secret,
  });

  const uri = totp.toString();

  const verifyCode = () => {
    const delta = totp.validate({ token: verificationCode, window: 1 });
    
    if (delta !== null) {
      setIsConfigured(true);
      toast({
        title: "2FA Configured Successfully",
        description: "Two-factor authentication has been set up.",
      });
    } else {
      toast({
        title: "Invalid Code",
        description: "Please check the code and try again.",
        variant: "destructive",
      });
    }
  };

  return (
    <Card className="w-full max-w-md mx-auto">
      <CardHeader>
        <CardTitle>Set Up Two-Factor Authentication</CardTitle>
        <CardDescription>
          Scan the QR code with your authenticator app and enter the verification code to complete setup.
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {!isConfigured ? (
          <>
            <div className="flex justify-center p-4 bg-white rounded-lg">
              <QRCodeSVG value={uri} size={200} />
            </div>
            <div className="space-y-4">
              <div className="space-y-2">
                <p className="text-sm text-muted-foreground">
                  Can't scan the QR code? Use this secret key:
                </p>
                <code className="block p-2 bg-muted rounded text-sm break-all">
                  {secret.base32}
                </code>
              </div>
              <div className="space-y-2">
                <Input
                  type="text"
                  placeholder="Enter verification code"
                  value={verificationCode}
                  onChange={(e) => setVerificationCode(e.target.value)}
                  maxLength={6}
                />
                <Button 
                  className="w-full" 
                  onClick={verifyCode}
                  disabled={verificationCode.length !== 6}
                >
                  Verify Code
                </Button>
              </div>
            </div>
          </>
        ) : (
          <div className="text-center space-y-4">
            <div className="text-green-500">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                className="h-16 w-16 mx-auto"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
            </div>
            <h3 className="text-xl font-semibold">Setup Complete!</h3>
            <p className="text-muted-foreground">
              Two-factor authentication has been successfully configured.
            </p>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default TwoFactorSetup;