#!/bin/bash
awk '!seen[$0] {print} {++seen[$0]}'