{ pkgs ? import <nixpkgs> { } }:

let
  php = pkgs.php85.buildEnv {
    extensions = ({ enabled, all }: enabled ++ [ all.xdebug ]);
    extraConfig = "xdebug.mode=coverage";
  };
in
pkgs.mkShell {
  packages = [
    php
    pkgs.php85Packages.composer
  ];

  XDEBUG_MODE = "coverage";
}
